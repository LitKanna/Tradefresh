<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RFQService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RFQController extends Controller
{
    protected $rfqService;

    public function __construct(RFQService $rfqService)
    {
        $this->rfqService = $rfqService;
    }

    /**
     * Create RFQ from weekly planner
     */
    public function createFromPlanner(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.name' => 'required|string',
                'items.*.totalQuantity' => 'required|numeric|min:0.1',
                'items.*.unit' => 'required|string',
                'items.*.days' => 'array',
                'delivery_date' => 'nullable|date',
                'delivery_time' => 'nullable|string',
                'special_instructions' => 'nullable|string',
            ]);

            // Get the authenticated buyer
            $buyer = auth('buyer')->user();
            if (!$buyer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please login to send RFQs'
                ], 401);
            }

            // Transform items to the expected format
            $items = [];
            foreach ($validated['items'] as $item) {
                $items[] = [
                    'product_name' => $item['name'],
                    'quantity' => $item['totalQuantity'],
                    'unit' => $item['unit'],
                    'category' => 'Fresh Produce',
                    'notes' => isset($item['days']) ? 'Days: ' . implode(', ', $item['days']) : null,
                ];
            }

            // Prepare RFQ data
            $rfqData = [
                'items' => $items,
                'delivery_date' => $validated['delivery_date'] ?? now()->addDays(2)->toDateString(),
                'delivery_time' => $validated['delivery_time'] ?? 'Morning',
                'special_instructions' => $validated['special_instructions'] ?? null,
            ];

            // Create the RFQ and broadcast it
            $rfq = $this->rfqService->createRFQFromPlanner($rfqData, $buyer->id);

            Log::info('RFQ created from planner', [
                'rfq_id' => $rfq->id,
                'buyer_id' => $buyer->id,
                'items_count' => count($items)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'RFQ sent successfully to all vendors!',
                'rfq_id' => $rfq->id,
                'reference_number' => $rfq->reference_number,
                'items_count' => count($items),
                'broadcast_status' => 'Sent to all online vendors via WebSocket'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create RFQ from planner', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send RFQ. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update RFQ and broadcast to all vendors
     */
    public function updateRFQ(Request $request)
    {
        try {
            $validated = $request->validate([
                'rfq_id' => 'required|integer|exists:r_f_q_s,id',
                'items' => 'required|array|min:1',
                'items.*.product_name' => 'required|string',
                'items.*.quantity' => 'required|numeric|min:0.1',
                'items.*.unit' => 'required|string',
                'broadcast_to_all' => 'boolean'
            ]);

            $buyer = auth('buyer')->user();
            if (!$buyer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please login to update RFQs'
                ], 401);
            }

            // Get the RFQ
            $rfq = \App\Models\RFQ::where('id', $validated['rfq_id'])
                ->where('buyer_id', $buyer->id)
                ->first();

            if (!$rfq) {
                return response()->json([
                    'success' => false,
                    'message' => 'RFQ not found or you do not have permission to update it'
                ], 404);
            }

            // Update the RFQ items
            $rfq->items = $validated['items'];
            $rfq->save();

            // Broadcast update to all vendors
            if ($request->get('broadcast_to_all', true)) {
                broadcast(new \App\Events\RFQUpdated($rfq))->toOthers();
            }

            Log::info('RFQ updated and broadcasted', [
                'rfq_id' => $rfq->id,
                'buyer_id' => $buyer->id,
                'items_count' => count($validated['items'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'RFQ updated and sent to all vendors',
                'rfq_id' => $rfq->id,
                'items_count' => count($validated['items'])
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update RFQ', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update RFQ',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update RFQ for a specific vendor only
     */
    public function updateForVendor(Request $request)
    {
        try {
            $validated = $request->validate([
                'rfq_id' => 'required|integer|exists:r_f_q_s,id',
                'vendor_id' => 'required|integer|exists:vendors,id',
                'items' => 'required|array|min:1',
                'items.*.product_name' => 'required|string',
                'items.*.quantity' => 'required|numeric|min:0.1',
                'items.*.unit' => 'required|string',
            ]);

            $buyer = auth('buyer')->user();
            if (!$buyer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please login to update RFQs'
                ], 401);
            }

            // Get the RFQ
            $rfq = \App\Models\RFQ::where('id', $validated['rfq_id'])
                ->where('buyer_id', $buyer->id)
                ->first();

            if (!$rfq) {
                return response()->json([
                    'success' => false,
                    'message' => 'RFQ not found or you do not have permission to update it'
                ], 404);
            }

            // Create a vendor-specific update (store in a separate field or create new quote request)
            // For now, we'll broadcast to the specific vendor only
            $vendorUpdate = [
                'rfq_id' => $rfq->id,
                'buyer_id' => $buyer->id,
                'vendor_id' => $validated['vendor_id'],
                'items' => $validated['items'],
                'type' => 'rfq_update',
                'timestamp' => now()
            ];

            // Broadcast to specific vendor channel
            broadcast(new \App\Events\VendorRFQUpdate($vendorUpdate))->toOthers();

            Log::info('RFQ update sent to specific vendor', [
                'rfq_id' => $rfq->id,
                'vendor_id' => $validated['vendor_id'],
                'buyer_id' => $buyer->id,
                'items_count' => count($validated['items'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Update sent to vendor successfully',
                'rfq_id' => $rfq->id,
                'vendor_id' => $validated['vendor_id'],
                'items_count' => count($validated['items'])
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send vendor-specific RFQ update', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send update to vendor',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}