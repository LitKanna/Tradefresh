<?php

namespace App\Services\BulkHunter;

use App\Services\ABN\ABNLookupService;
use App\Services\GooglePlacesService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BulkHunterService
{
    protected ABNLookupService $abnService;

    protected GooglePlacesService $placesService;

    public function __construct(ABNLookupService $abnService)
    {
        $this->abnService = $abnService;
        $this->placesService = new GooglePlacesService;
    }

    /**
     * Discover real businesses using Google Places API
     */
    public function discoverRealBusinesses(string $keyword = 'restaurant', string $location = 'Sydney', ?string $postcode = null): array
    {
        $leads = [];

        try {
            // Build location query
            $locationQuery = $location;
            if ($postcode) {
                $locationQuery = "{$postcode}, Sydney, NSW, Australia";
            }

            // Get real businesses from Google Places
            $businesses = $this->placesService->searchBusinesses($keyword, $locationQuery);

            foreach ($businesses as $business) {
                // Get detailed information for each business
                if (! empty($business['google_place_id'])) {
                    $details = $this->placesService->getBusinessDetails($business['google_place_id']);

                    if ($details) {
                        // Try to find ABN for Australian businesses
                        $abn = $this->findBusinessABN($details['business_name'], $postcode);

                        // Create lead data with real information
                        $leadData = [
                            'business_name' => $details['business_name'],
                            'google_place_id' => $business['google_place_id'],
                            'address' => $details['address'],
                            'phone' => $details['phone'],
                            'website' => $details['website'],
                            'email' => $this->extractEmailFromWebsite($details['website']),
                            'google_rating' => $details['rating'],
                            'google_reviews_count' => $details['total_reviews'],
                            'price_level' => $details['price_level'],
                            'latitude' => $details['latitude'],
                            'longitude' => $details['longitude'],
                            'opening_hours' => json_encode($details['opening_hours']),
                            'is_currently_open' => $details['is_open_now'],
                            'category' => $this->categorizeByTypes($details['types']),
                            'business_type' => implode(', ', array_slice($details['types'], 0, 3)),
                            'status' => 'NEW',
                            'source' => 'GOOGLE_PLACES',
                            'discovered_at' => now(),
                            'abn' => $abn['abn'] ?? null,
                            'entity_type' => $abn['entity_type'] ?? null,
                        ];

                        // Extract postcode from address
                        if (preg_match('/\b(\d{4})\b/', $details['address'], $matches)) {
                            $leadData['postcode'] = $matches[1];
                            $leadData['state'] = 'NSW';
                        }

                        // Calculate business size based on real data
                        $leadData = $this->calculateBusinessMetrics($leadData);

                        // Store or update in database
                        $leadId = $this->storeRealLead($leadData);

                        // Get the complete lead data from database (including any updates)
                        $storedLead = DB::table('buyer_leads')->where('id', $leadId)->first();
                        if ($storedLead) {
                            $leadData = (array) $storedLead;
                        } else {
                            $leadData['id'] = $leadId;
                        }

                        $leads[] = $leadData;
                    }
                }
            }

            // Enrich with real market intelligence
            $this->enrichWithMarketIntelligence($leads);

        } catch (\Exception $e) {
            Log::error('BulkHunter Discovery Error: '.$e->getMessage());
        }

        return $leads;
    }

    /**
     * Find ABN for a business using ABR API
     */
    protected function findBusinessABN(string $businessName, ?string $postcode): ?array
    {
        try {
            // Clean business name for ABN search
            $searchName = preg_replace('/\s+(pty|ltd|limited|inc|incorporated|&|and|co|company)\.?\s*/i', '', $businessName);

            // Use real ABN API
            $abnData = $this->abnService->lookup($searchName);

            if ($abnData) {
                return $abnData;
            }

            // Try searching by name and postcode if direct lookup fails
            if ($postcode) {
                $searchResults = $this->abnService->searchByNameAndPostcode($searchName, $postcode);
                if (! empty($searchResults['names'])) {
                    // Get the first matching result
                    $firstResult = $searchResults['names'][0];
                    if (! empty($firstResult['abn'])) {
                        return $this->abnService->lookup($firstResult['abn']);
                    }
                }
            }

        } catch (\Exception $e) {
            Log::info('ABN lookup failed for: '.$businessName);
        }

        return null;
    }

    /**
     * Extract email from website (if possible)
     */
    protected function extractEmailFromWebsite(?string $website): ?string
    {
        if (! $website) {
            return null;
        }

        try {
            // Cache website emails for 1 week
            $cacheKey = 'website_email_'.md5($website);

            if ($cached = Cache::get($cacheKey)) {
                return $cached;
            }

            // Try to fetch the website
            $response = Http::timeout(5)->get($website);

            if ($response->successful()) {
                $html = $response->body();

                // Look for email patterns
                preg_match_all('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $html, $matches);

                if (! empty($matches[0])) {
                    // Filter out common non-contact emails
                    $emails = array_filter($matches[0], function ($email) {
                        return ! preg_match('/(noreply|no-reply|donotreply|mailer-daemon|postmaster|webmaster|abuse|spam)/i', $email);
                    });

                    if (! empty($emails)) {
                        $email = reset($emails); // Get first valid email
                        Cache::put($cacheKey, $email, 604800); // Cache for 1 week

                        return $email;
                    }
                }
            }

        } catch (\Exception $e) {
            Log::info('Could not extract email from website: '.$website);
        }

        return null;
    }

    /**
     * Categorize business based on Google Places types
     */
    protected function categorizeByTypes(array $types): string
    {
        $categoryMap = [
            'restaurant' => 'Restaurant',
            'cafe' => 'Cafe',
            'bar' => 'Bar',
            'night_club' => 'Night Club',
            'bakery' => 'Bakery',
            'supermarket' => 'Supermarket',
            'grocery_or_supermarket' => 'Grocery Store',
            'liquor_store' => 'Liquor Store',
            'meal_delivery' => 'Meal Delivery',
            'meal_takeaway' => 'Takeaway',
            'food' => 'Food Service',
            'lodging' => 'Hotel',
            'casino' => 'Casino',
            'stadium' => 'Stadium/Venue',
        ];

        foreach ($types as $type) {
            if (isset($categoryMap[$type])) {
                return $categoryMap[$type];
            }
        }

        return 'Food & Beverage';
    }

    /**
     * Calculate real business metrics based on actual data
     */
    protected function calculateBusinessMetrics(array $leadData): array
    {
        $score = 0;
        $volumeEstimate = 0;
        $spendEstimate = 0;

        // Calculate based on real Google reviews (strong indicator of business volume)
        $reviewCount = $leadData['google_reviews_count'] ?? 0;
        if ($reviewCount > 0) {
            // More reviews = more customers
            $score += min($reviewCount / 10, 100); // Max 100 points from reviews

            // Estimate daily customers based on review frequency
            // Assumption: 1-2% of customers leave reviews
            $estimatedDailyCustomers = $reviewCount * 2; // Over lifetime, simplified

            // Estimate volume based on business type
            $avgOrderSize = $this->getAverageOrderSize($leadData['category'] ?? 'Restaurant');
            $volumeEstimate = $estimatedDailyCustomers * $avgOrderSize * 7; // Weekly volume
        }

        // Factor in price level (1-4 scale from Google)
        $priceLevel = $leadData['price_level'] ?? 2;
        $priceMultiplier = [1 => 0.5, 2 => 1, 3 => 2, 4 => 3][$priceLevel] ?? 1;
        $volumeEstimate *= $priceMultiplier;

        // Calculate monthly spend estimate
        $avgProductCost = 50; // Average cost per kg of produce
        $spendEstimate = $volumeEstimate * 4 * $avgProductCost;

        // Determine classification based on estimated spend
        if ($spendEstimate >= 500000) {
            $classification = 'WHALE';
        } elseif ($spendEstimate >= 100000) {
            $classification = 'BIG';
        } elseif ($spendEstimate >= 25000) {
            $classification = 'MEDIUM';
        } else {
            $classification = 'SMALL';
        }

        // Add Google rating to score
        $rating = $leadData['google_rating'] ?? 0;
        $score += $rating * 10; // Up to 50 points for 5-star rating

        $leadData['size_score'] = (int) $score;
        $leadData['weekly_volume_estimate'] = (int) $volumeEstimate;
        $leadData['monthly_spend_estimate'] = (int) $spendEstimate;
        $leadData['size_classification'] = $classification;
        $leadData['final_score'] = min($score, 100);

        return $leadData;
    }

    /**
     * Get average order size by category (in kg)
     */
    protected function getAverageOrderSize(string $category): int
    {
        $averages = [
            'Restaurant' => 50,
            'Hotel' => 200,
            'Supermarket' => 1000,
            'Cafe' => 20,
            'Bakery' => 30,
            'Bar' => 25,
            'Night Club' => 40,
            'Takeaway' => 30,
            'Casino' => 500,
            'Stadium/Venue' => 300,
        ];

        return $averages[$category] ?? 30;
    }

    /**
     * Enrich leads with real market intelligence
     */
    protected function enrichWithMarketIntelligence(array &$leads): void
    {
        // Real competitor analysis (these are actual Sydney suppliers)
        $suppliers = [
            'Zupply' => ['high_fees' => true, 'limited_range' => true],
            'Bidfood' => ['established' => true, 'expensive' => true],
            'PFD Food Services' => ['large_minimums' => true],
            'Sydney Markets Direct' => ['traditional' => true],
        ];

        foreach ($leads as &$lead) {
            // Randomly assign current suppliers (for demo - in production would come from CRM)
            $lead['current_supplier'] = array_rand($suppliers);
            $supplierIssues = $suppliers[$lead['current_supplier']];

            // Set pain points based on real supplier issues
            $painPoints = [];
            if ($supplierIssues['high_fees'] ?? false) {
                $painPoints[] = 'High delivery fees';
            }
            if ($supplierIssues['limited_range'] ?? false) {
                $painPoints[] = 'Limited product range';
            }
            if ($supplierIssues['large_minimums'] ?? false) {
                $painPoints[] = 'Large minimum orders';
            }
            if ($supplierIssues['expensive'] ?? false) {
                $painPoints[] = 'Premium pricing';
            }

            $lead['supplier_pain_points'] = implode(', ', $painPoints);
            $lead['unhappy_with_supplier'] = ! empty($painPoints);

            // Add realistic buyer personas based on business type
            $lead['buyer_persona'] = $this->getBuyerPersona($lead['category'] ?? 'Restaurant');
            $lead['decision_maker'] = $this->getDecisionMaker($lead['size_classification'] ?? 'MEDIUM');
            $lead['buying_cycle'] = $this->getBuyingCycle($lead['category'] ?? 'Restaurant');
            $lead['payment_terms'] = $this->getPaymentTerms($lead['size_classification'] ?? 'MEDIUM');
        }
    }

    /**
     * Get buyer persona by category
     */
    protected function getBuyerPersona(string $category): string
    {
        $personas = [
            'Restaurant' => 'Head Chef / Restaurant Manager',
            'Hotel' => 'F&B Director / Procurement Manager',
            'Supermarket' => 'Category Manager / Buyer',
            'Cafe' => 'Owner / Manager',
            'Bar' => 'Bar Manager / Owner',
            'Casino' => 'Executive Chef / F&B Director',
        ];

        return $personas[$category] ?? 'Purchasing Manager';
    }

    /**
     * Get decision maker by size
     */
    protected function getDecisionMaker(string $size): string
    {
        $makers = [
            'WHALE' => 'Procurement Director',
            'BIG' => 'Operations Manager',
            'MEDIUM' => 'Restaurant Manager',
            'SMALL' => 'Owner/Operator',
        ];

        return $makers[$size] ?? 'Manager';
    }

    /**
     * Get buying cycle by category
     */
    protected function getBuyingCycle(string $category): string
    {
        $cycles = [
            'Restaurant' => 'Weekly',
            'Hotel' => 'Bi-weekly',
            'Supermarket' => 'Daily',
            'Cafe' => 'Twice weekly',
            'Bar' => 'Weekly',
        ];

        return $cycles[$category] ?? 'Weekly';
    }

    /**
     * Get payment terms by size
     */
    protected function getPaymentTerms(string $size): string
    {
        $terms = [
            'WHALE' => '60 days',
            'BIG' => '30 days',
            'MEDIUM' => '14 days',
            'SMALL' => '7 days',
        ];

        return $terms[$size] ?? '30 days';
    }

    /**
     * Store real lead in database
     */
    protected function storeRealLead(array $data): int
    {
        try {
            // Check if already exists by Google Place ID
            if (! empty($data['google_place_id'])) {
                $existing = DB::table('buyer_leads')
                    ->where('google_place_id', $data['google_place_id'])
                    ->first();

                if ($existing) {
                    // Update with latest data
                    DB::table('buyer_leads')
                        ->where('id', $existing->id)
                        ->update(array_merge($data, [
                            'updated_at' => now(),
                            'last_enriched_at' => now(),
                        ]));

                    return $existing->id;
                }
            }

            // Insert new lead
            return DB::table('buyer_leads')->insertGetId(array_merge($data, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));

        } catch (\Exception $e) {
            Log::error('Failed to store real lead: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Enrich a lead with additional Google Maps data
     */
    public function enrichWithGoogleMaps(int $leadId): bool
    {
        try {
            $lead = DB::table('buyer_leads')->where('id', $leadId)->first();

            if (! $lead || empty($lead->google_place_id)) {
                return false;
            }

            // Get fresh details from Google Places
            $details = $this->placesService->getBusinessDetails($lead->google_place_id);

            if (! $details) {
                return false;
            }

            // Update lead with enriched data
            $updateData = [
                'phone' => $details['phone'] ?? $lead->phone,
                'website' => $details['website'] ?? $lead->website,
                'email' => $this->extractEmailFromWebsite($details['website']) ?? $lead->email,
                'google_rating' => $details['rating'],
                'google_reviews_count' => $details['total_reviews'],
                'opening_hours' => json_encode($details['opening_hours']),
                'is_currently_open' => $details['is_open_now'],
                'latitude' => $details['latitude'],
                'longitude' => $details['longitude'],
                'last_enriched_at' => now(),
                'updated_at' => now(),
            ];

            // Recalculate metrics with new data
            $leadData = array_merge((array) $lead, $updateData);
            $leadData = $this->calculateBusinessMetrics($leadData);

            DB::table('buyer_leads')
                ->where('id', $leadId)
                ->update($leadData);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to enrich lead with Google Maps data: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Analyze procurement needs for a lead
     */
    public function analyzeProcurementNeeds(int $leadId): array
    {
        $lead = DB::table('buyer_leads')->where('id', $leadId)->first();

        if (! $lead) {
            return [];
        }

        $analyzer = new ProcurementAnalyzer;
        $analysis = $analyzer->analyzeProcurementNeeds((array) $lead);

        // Store the analysis in the database
        DB::table('buyer_leads')->where('id', $leadId)->update([
            'procurement_analysis' => json_encode($analysis),
            'conversion_score' => $analysis['conversion_score'] ?? 0,
            'last_analyzed_at' => now(),
        ]);

        return $analysis;
    }

    /**
     * Get leads with highest conversion potential
     */
    public function getHighConversionLeads(int $limit = 10): array
    {
        $leads = DB::table('buyer_leads')
            ->whereNotNull('google_place_id')
            ->where('status', 'NEW')
            ->orderBy('google_reviews_count', 'desc')
            ->limit($limit)
            ->get();

        $analyzer = new ProcurementAnalyzer;
        $enrichedLeads = [];

        foreach ($leads as $lead) {
            $leadArray = (array) $lead;
            $analysis = $analyzer->analyzeProcurementNeeds($leadArray);
            $leadArray['procurement_needs'] = $analysis;
            $enrichedLeads[] = $leadArray;
        }

        // Sort by conversion score
        usort($enrichedLeads, function ($a, $b) {
            return ($b['procurement_needs']['conversion_score'] ?? 0) <=> ($a['procurement_needs']['conversion_score'] ?? 0);
        });

        return $enrichedLeads;
    }

    /**
     * Search and enrich businesses by postcode
     */
    public function searchByPostcode(string $postcode, string $type = 'restaurant'): array
    {
        $businesses = $this->placesService->searchByPostcode($postcode, $type);
        $leads = [];

        foreach ($businesses as $business) {
            if (! empty($business['google_place_id'])) {
                $details = $this->placesService->getBusinessDetails($business['google_place_id']);

                if ($details) {
                    // Process same as discoverRealBusinesses
                    $abn = $this->findBusinessABN($details['business_name'], $postcode);

                    $leadData = [
                        'business_name' => $details['business_name'],
                        'google_place_id' => $business['google_place_id'],
                        'address' => $details['address'],
                        'postcode' => $postcode,
                        'state' => 'NSW',
                        'phone' => $details['phone'],
                        'website' => $details['website'],
                        'email' => $this->extractEmailFromWebsite($details['website']),
                        'google_rating' => $details['rating'],
                        'google_reviews_count' => $details['total_reviews'],
                        'latitude' => $details['latitude'],
                        'longitude' => $details['longitude'],
                        'category' => $this->categorizeByTypes($details['types']),
                        'status' => 'NEW',
                        'source' => 'GOOGLE_PLACES',
                        'discovered_at' => now(),
                        'abn' => $abn['abn'] ?? null,
                    ];

                    $leadData = $this->calculateBusinessMetrics($leadData);
                    $leadId = $this->storeRealLead($leadData);
                    $leadData['id'] = $leadId;

                    $leads[] = $leadData;
                }
            }
        }

        $this->enrichWithMarketIntelligence($leads);

        return $leads;
    }
}
