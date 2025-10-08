<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ABN\ABNLookupService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ABN Lookup Controller
 *
 * Provides API endpoints for ABN validation and lookup using the
 * Australian Business Register (ABR) SearchByABNv202001 API.
 *
 * ENDPOINTS:
 * - POST /api/abn/lookup - Lookup business details by ABN
 * - GET /api/abn/validate/{abn} - Validate ABN format and checksum
 *
 * INTEGRATION POINTS:
 * - Frontend: AJAX calls from buyer/vendor registration forms
 * - Backend: Business entity verification during onboarding
 * - Cache: 24-hour TTL for successful lookups
 *
 * SECURITY:
 * - Rate limiting: 60 requests per minute per IP
 * - Input validation: ABN format validation
 * - GUID protection: Never exposed in responses
 */
class ABNLookupController extends Controller
{
    protected ABNLookupService $abnService;

    public function __construct(ABNLookupService $abnService)
    {
        $this->abnService = $abnService;
    }

    /**
     * Lookup business details by ABN
     *
     * REQUEST:
     * POST /api/abn/lookup
     * {
     *   "abn": "11 222 333 444",
     *   "include_historical": false
     * }
     *
     * RESPONSE (Success):
     * {
     *   "success": true,
     *   "data": {
     *     "abn": "11222333444",
     *     "entity_name": "EXAMPLE COMPANY PTY LTD",
     *     "is_active": true,
     *     "entity_type": {
     *       "code": "PRV",
     *       "description": "Australian Private Company"
     *     },
     *     "gst": {
     *       "registered": true,
     *       "effective_from": "2000-07-01"
     *     },
     *     "address": {
     *       "state_code": "NSW",
     *       "postcode": "2000"
     *     }
     *   }
     * }
     *
     * RESPONSE (Not Found):
     * {
     *   "success": false,
     *   "message": "ABN not found",
     *   "data": null
     * }
     *
     * RESPONSE (Error):
     * {
     *   "success": false,
     *   "message": "Service temporarily unavailable",
     *   "error": "API timeout"
     * }
     */
    public function lookup(Request $request): JsonResponse
    {
        // Validate request
        $request->validate([
            'abn' => 'required|string|min:11|max:14', // Allow spaces
            'include_historical' => 'boolean',
        ]);

        $abn = $request->input('abn');
        $cleanAbn = preg_replace('/\D/', '', $abn);
        $includeHistorical = $request->input('include_historical', false);

        try {
            // Check if data is in cache before calling service
            $cacheKey = 'abn_'.$cleanAbn;
            $isCached = Cache::has($cacheKey);

            // Perform ABN lookup (service handles its own caching)
            $businessData = $this->abnService->lookup($abn);

            if ($businessData === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'ABN not found',
                    'data' => null,
                ], 404);
            }

            // Return successful response in format expected by JavaScript
            return response()->json([
                'success' => true,
                'businessName' => $businessData['entity_name'], // JavaScript expects this field
                'data' => $this->formatBusinessData($businessData),
                'cached' => $isCached, // Now actually tracks cache status
                'message' => 'ABN verified successfully',
                'address' => [
                    'state' => $businessData['address_state_code'] ?? '',
                    'postcode' => $businessData['address_postcode'] ?? '',
                ],
            ]);

        } catch (Exception $e) {
            // Log error
            Log::error('ABN lookup failed', [
                'abn' => substr($abn, 0, 2).'***'.substr($abn, -2),
                'error' => $e->getMessage(),
            ]);

            // Check if it's a rate limit error
            if (strpos($e->getMessage(), 'Rate limit') !== false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many requests. Please try again later.',
                    'error' => 'rate_limit_exceeded',
                ], 429);
            }

            // Return generic error response
            return response()->json([
                'success' => false,
                'message' => 'Unable to verify ABN. Please try again later.',
                'error' => 'api_error',
            ], 503);
        }
    }

    /**
     * Validate ABN from request body (POST method)
     *
     * REQUEST:
     * POST /api/abn/validate
     * { "abn": "11 222 333 444" }
     */
    public function validateAbnRequest(Request $request): JsonResponse
    {
        $request->validate([
            'abn' => 'required|string|min:11|max:14',
        ]);

        return $this->validateABN($request->input('abn'));
    }

    /**
     * Validate ABN format and checksum (route parameter method)
     *
     * REQUEST:
     * GET /api/abn/validate/{abn}
     *
     * RESPONSE:
     * {
     *   "valid": true,
     *   "formatted": "11 222 333 444",
     *   "checksum_valid": true,
     *   "format_valid": true
     * }
     */
    public function validateABN(string $abn): JsonResponse
    {
        // Clean ABN
        $cleanAbn = preg_replace('/[^0-9]/', '', $abn);

        // Cache validation results for 60 seconds to prevent duplicate calculations
        $cacheKey = 'abn_validation:'.$cleanAbn;

        return Cache::remember($cacheKey, 60, function () use ($cleanAbn) {
            // Check format
            $formatValid = strlen($cleanAbn) === 11 && ctype_digit($cleanAbn);

            // Check checksum
            $checksumValid = false;
            if ($formatValid) {
                $checksumValid = $this->validateABNChecksum($cleanAbn);
            }

            // Use formatABN method instead of inline formatting
            $formatted = $formatValid ? $this->formatABN($cleanAbn) : '';

            return response()->json([
                'valid' => $formatValid && $checksumValid,
                'formatted' => $formatted,
                'checksum_valid' => $checksumValid,
                'format_valid' => $formatValid,
            ]);
        });
    }

    /**
     * Validate ABN checksum using modulus 89 algorithm
     *
     * @param  string  $abn  Clean 11-digit ABN
     */
    protected function validateABNChecksum(string $abn): bool
    {
        return $this->abnService->validateChecksum($abn);
    }

    /**
     * Format business data for response
     *
     * @param  array  $data  Raw business data from service
     * @return array Formatted data for API response
     */
    protected function formatBusinessData(array $data): array
    {
        return [
            'abn' => $data['abn'] ?? '',
            'abn_formatted' => $this->formatABN($data['abn'] ?? ''),
            'entity_name' => $data['entity_name'] ?? '',
            'is_active' => ($data['abn_status'] ?? '') === 'active',
            'abn_status' => $data['abn_status'] ?? 'unknown',
            'entity_type' => $data['entity_type'] ?? 'Unknown',
            'gst' => [
                'registered' => $data['gst_registered'] ?? false,
            ],
            'address' => [
                'state_code' => $data['address_state_code'] ?? '',
                'postcode' => $data['address_postcode'] ?? '',
            ],
            'trading_names' => $data['trading_names'] ?? [],
            'retrieved_at' => $data['last_updated'] ?? now()->toDateTimeString(),
        ];
    }

    /**
     * Format ABN for display
     */
    protected function formatABN(string $abn): string
    {
        $clean = preg_replace('/[^0-9]/', '', $abn);

        if (strlen($clean) !== 11) {
            return $abn;
        }

        return substr($clean, 0, 2).' '.
               substr($clean, 2, 3).' '.
               substr($clean, 5, 3).' '.
               substr($clean, 8, 3);
    }
}
