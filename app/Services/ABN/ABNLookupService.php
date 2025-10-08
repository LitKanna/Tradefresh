<?php

namespace App\Services\ABN;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ABNLookupService
{
    protected string $apiGuid;
    protected string $baseUrl;
    protected int $timeout;
    protected bool $cacheEnabled;
    protected int $cacheTtl;

    public function __construct()
    {
        // Use the user's actual ABR GUID
        $this->apiGuid = env('ABN_API_GUID', '00805b10-ccd8-4eea-8ff5-88376e6161fe');
        $this->baseUrl = config('abn.api.base_url', 'https://abr.business.gov.au');
        $this->timeout = config('abn.api.timeout', 30);
        $this->cacheEnabled = config('abn.cache.enabled', true);
        $this->cacheTtl = config('abn.cache.ttl', 86400);
    }

    /**
     * Lookup ABN details from the ABR API
     */
    public function lookup(string $abn): ?array
    {
        // Clean the ABN
        $abn = preg_replace('/\D/', '', $abn);

        // Check cache first
        if ($this->cacheEnabled) {
            $cacheKey = 'abn_' . $abn;
            $cached = Cache::get($cacheKey);
            if ($cached) {
                Log::info('ABN retrieved from cache', ['abn' => $abn]);
                return $cached;
            }
        }

        try {
            // Build the API URL using the correct v202001 endpoint
            $url = sprintf(
                '%s/abrxmlsearch/AbrXmlSearch.asmx/SearchByABNv202001',
                rtrim($this->baseUrl, '/')
            );

            // Make the API request with correct parameters
            $response = Http::timeout($this->timeout)
                ->get($url, [
                    'searchString' => $abn,
                    'includeHistoricalDetails' => 'N',
                    'authenticationGuid' => $this->apiGuid
                ]);

            if (!$response->successful()) {
                Log::warning('ABN API request failed', [
                    'abn' => $abn,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }

            // Parse the XML response
            $data = $this->parseXmlResponse($response->body(), $abn);

            // Cache the result
            if ($data && $this->cacheEnabled) {
                Cache::put($cacheKey, $data, $this->cacheTtl);
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('ABN lookup failed', [
                'abn' => $abn,
                'error' => $e->getMessage()
            ]);

            // Only use mock data if API completely fails and we're in development
            // Comment this out to force real API usage
            // if (app()->environment('local')) {
            //     return $this->getMockData($abn);
            // }

            return null;
        }
    }

    /**
     * Parse the XML response from ABR API
     */
    protected function parseXmlResponse(string $xmlString, string $abn): ?array
    {
        try {
            // Parse the XML response
            $xml = simplexml_load_string($xmlString);

            if (!$xml) {
                Log::warning('Failed to parse XML response', ['abn' => $abn]);
                return null;
            }

            // Check if this is the root ABRPayloadSearchResults element
            if ($xml->getName() === 'ABRPayloadSearchResults') {
                // Direct access to response element
                $response = $xml->response ?? null;
            } else {
                // Try XPath for other structures
                $xml->registerXPathNamespace('abr', 'http://abr.business.gov.au/ABRXMLSearch/');
                $response = $xml->xpath('//abr:response')[0] ?? $xml->xpath('//response')[0] ?? null;
            }

            if (!$response) {
                Log::warning('No response element in XML', ['abn' => $abn]);
                return null;
            }

            // Check for business entity in different formats (including v202001)
            $businessEntity = $response->businessEntity202001 ??
                             $response->businessEntity201408 ??
                             $response->businessEntity201205 ??
                             $response->businessEntity200709 ??
                             $response->businessEntity ?? null;

            if (!$businessEntity) {
                // Check if it's an exception (ABN not found)
                if (isset($response->exception)) {
                    Log::info('ABN not found in ABR', ['abn' => $abn]);
                    return null;
                }
                Log::warning('No business entity in response', ['abn' => $abn]);
                return null;
            }

            // Extract ABN status from entityStatus
            $abnStatus = 'Unknown';
            if (isset($businessEntity->entityStatus) && isset($businessEntity->entityStatus->entityStatusCode)) {
                $abnStatus = trim((string) $businessEntity->entityStatus->entityStatusCode);
            } elseif (isset($businessEntity->ABN) && isset($businessEntity->ABN->identifierStatus)) {
                $abnStatus = trim((string) $businessEntity->ABN->identifierStatus);
            }

            // Extract entity name - try multiple locations
            $entityName = '';

            // Try main name first
            if (isset($businessEntity->mainName)) {
                $entityName = (string) ($businessEntity->mainName->organisationName ?? '');
            }

            // If no main name, try main trading name
            if (empty($entityName) && isset($businessEntity->mainTradingName)) {
                $entityName = (string) ($businessEntity->mainTradingName->organisationName ?? '');
            }

            // If still empty, try legal name
            if (empty($entityName) && isset($businessEntity->legalName)) {
                if (isset($businessEntity->legalName->fullName)) {
                    $entityName = (string) $businessEntity->legalName->fullName;
                } else {
                    $firstName = (string) ($businessEntity->legalName->givenName ?? '');
                    $lastName = (string) ($businessEntity->legalName->familyName ?? '');
                    if ($firstName || $lastName) {
                        $entityName = trim($firstName . ' ' . $lastName);
                    }
                }
            }

            // Extract trading names
            $tradingNames = [];
            if (isset($businessEntity->businessName)) {
                foreach ($businessEntity->businessName as $name) {
                    $tradingName = (string) ($name->organisationName ?? '');
                    if (!empty($tradingName)) {
                        $tradingNames[] = $tradingName;
                    }
                }
            }

            // Extract address
            $stateCode = '';
            $postcode = '';

            $address = $businessEntity->mainBusinessPhysicalAddress ?? null;
            if ($address) {
                $stateCode = (string) ($address->stateCode ?? '');
                $postcode = (string) ($address->postcode ?? '');
            }

            // Check GST registration
            $gstRegistered = false;
            if (isset($businessEntity->goodsAndServicesTax)) {
                $gstFrom = (string) ($businessEntity->goodsAndServicesTax->effectiveFrom ?? '');
                $gstTo = (string) ($businessEntity->goodsAndServicesTax->effectiveTo ?? '');
                // If there's an effective from date and no end date (or end date is in future), then GST registered
                $gstRegistered = !empty($gstFrom) && (empty($gstTo) || strtotime($gstTo) > time());
            }

            // Get entity type
            $entityType = '';
            if (isset($businessEntity->entityType)) {
                $entityType = (string) ($businessEntity->entityType->entityDescription ??
                                       $businessEntity->entityType->entityTypeCode ?? '');
            }

            return [
                'abn' => $abn,
                'entity_name' => $entityName ?: 'Business Name Not Available',
                'trading_names' => $tradingNames,
                'abn_status' => (strtolower($abnStatus) === 'active' || $abnStatus === 'Active') ? 'active' : 'inactive',
                'entity_type' => $entityType ?: 'Unknown',
                'gst_registered' => $gstRegistered,
                'address_state_code' => $stateCode,
                'address_postcode' => $postcode,
                'data_source' => 'abr_api',
                'last_updated' => now()->toDateTimeString()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to parse ABN XML response', [
                'error' => $e->getMessage(),
                'abn' => $abn,
                'xml_snippet' => substr($xmlString, 0, 500)
            ]);
            return null;
        }
    }

    /**
     * Get mock data for development
     */
    protected function getMockData(string $abn): array
    {
        // Common test ABNs with realistic business names
        $testData = [
            '62944969621' => [
                'entity_name' => 'Premium Fresh Produce Sydney Pty Ltd',
                'trading_names' => ['Premium Fresh', 'Sydney Fresh Markets'],
                'abn_status' => 'active',
                'entity_type' => 'Company',
                'gst_registered' => true,
                'address_state_code' => 'NSW',
                'address_postcode' => '2129'
            ],
            '54683292551' => [
                'entity_name' => 'Fresh Produce Distributors Pty Ltd',
                'trading_names' => ['FPD Sydney', 'Fresh Direct'],
                'abn_status' => 'active',
                'entity_type' => 'Company',
                'gst_registered' => true,
                'address_state_code' => 'NSW',
                'address_postcode' => '2129'
            ],
            '51824753556' => [
                'entity_name' => 'Sydney Markets Wholesale Pty Ltd',
                'trading_names' => ['SM Wholesale'],
                'abn_status' => 'active',
                'entity_type' => 'Company',
                'gst_registered' => true,
                'address_state_code' => 'NSW',
                'address_postcode' => '2000'
            ],
            '11223344556' => [
                'entity_name' => 'Quality Fruits & Vegetables Pty Ltd',
                'trading_names' => ['QF&V', 'Quality Fresh'],
                'abn_status' => 'active',
                'entity_type' => 'Company',
                'gst_registered' => true,
                'address_state_code' => 'NSW',
                'address_postcode' => '2150'
            ]
        ];

        // Return specific test data or generate generic
        $data = $testData[$abn] ?? [
            'entity_name' => 'Test Business ' . substr($abn, -4),
            'trading_names' => ['Test Trading Name'],
            'abn_status' => 'active',
            'entity_type' => 'Company',
            'gst_registered' => true,
            'address_state_code' => 'NSW',
            'address_postcode' => '2000'
        ];

        return array_merge($data, [
            'abn' => $abn,
            'data_source' => 'mock_data',
            'last_updated' => now()->toDateTimeString()
        ]);
    }

    /**
     * Search for businesses by name and postcode
     */
    public function searchByNameAndPostcode(string $name, string $postcode): array
    {
        try {
            // For Phase 1, we'll use mock data since ABR API doesn't support name search directly
            // In production, this would integrate with ABR's name search API endpoint

            // Mock data for testing
            $mockResults = [
                'names' => []
            ];

            // Generate some mock restaurant data for Sydney postcodes
            if (in_array($postcode, ['2000', '2001', '2010', '2020', '2140'])) {
                $mockResults['names'] = [
                    [
                        'abn' => '12345678901',
                        'organisationName' => ucwords($name) . ' Sydney Pty Ltd',
                        'businessName' => ucwords($name) . ' ' . $postcode,
                        'entityTypeName' => 'Company',
                        'stateCode' => 'NSW',
                        'postcode' => $postcode,
                        'isCurrentIndicator' => 'Y'
                    ],
                    [
                        'abn' => '98765432101',
                        'organisationName' => 'Premium ' . ucwords($name) . ' Group',
                        'businessName' => ucwords($name) . ' Express',
                        'entityTypeName' => 'Company',
                        'stateCode' => 'NSW',
                        'postcode' => $postcode,
                        'isCurrentIndicator' => 'Y'
                    ]
                ];
            }

            return $mockResults;

        } catch (\Exception $e) {
            Log::error('Search by name failed', [
                'name' => $name,
                'postcode' => $postcode,
                'error' => $e->getMessage()
            ]);
            return ['names' => []];
        }
    }

    /**
     * Validate ABN checksum
     */
    public function validateChecksum(string $abn): bool
    {
        $abn = preg_replace('/\D/', '', $abn);

        if (strlen($abn) !== 11) {
            return false;
        }

        $weights = [10, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19];
        $sum = 0;

        for ($i = 0; $i < 11; $i++) {
            $digit = (int) $abn[$i];
            if ($i === 0) {
                $digit -= 1;
            }
            $sum += $digit * $weights[$i];
        }

        return $sum % 89 === 0;
    }
}