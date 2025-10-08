<?php

namespace App\Services\BulkHunter;

class ProcurementAnalyzer
{
    /**
     * Analyze what products a business likely needs based on their type
     */
    public function analyzeProcurementNeeds(array $businessData): array
    {
        $businessType = $businessData['category'] ?? '';
        $googleTypes = $businessData['types'] ?? [];
        $priceLevel = $businessData['price_level'] ?? 2;

        // Get base products needed
        $products = $this->getProductsByBusinessType($businessType, $googleTypes);

        // Estimate volumes based on business size
        $volumes = $this->estimateVolumes($businessData);

        // Analyze procurement patterns
        $patterns = $this->getProcurementPatterns($businessType);

        // Identify pain points for conversion
        $painPoints = $this->identifyPainPoints($businessData);

        // Create targeted approach strategy
        $approach = $this->createApproachStrategy($businessData, $products, $painPoints);

        return [
            'likely_products' => $products,
            'estimated_volumes' => $volumes,
            'procurement_patterns' => $patterns,
            'pain_points' => $painPoints,
            'approach_strategy' => $approach,
            'conversion_score' => $this->calculateConversionScore($businessData, $painPoints),
            'best_contact_time' => $this->getBestContactTime($businessType),
            'decision_makers' => $this->getDecisionMakers($businessData)
        ];
    }

    /**
     * Map business types to their likely product needs
     */
    protected function getProductsByBusinessType(string $businessType, array $googleTypes): array
    {
        $productMap = [
            'Restaurant' => [
                'vegetables' => [
                    'tomatoes' => ['fresh_tomatoes', 'roma_tomatoes', 'cherry_tomatoes', 'tomato_paste'],
                    'greens' => ['lettuce', 'spinach', 'rocket', 'mixed_greens'],
                    'onions' => ['brown_onions', 'red_onions', 'spring_onions', 'shallots'],
                    'root_vegetables' => ['potatoes', 'carrots', 'sweet_potatoes'],
                    'asian_vegetables' => ['bok_choy', 'chinese_cabbage', 'bean_sprouts'],
                    'herbs' => ['basil', 'parsley', 'coriander', 'thyme', 'rosemary'],
                    'specialty' => ['okra', 'eggplant', 'zucchini', 'capsicum']
                ],
                'fruits' => ['lemons', 'limes', 'oranges', 'berries'],
                'dairy' => ['milk', 'cream', 'cheese', 'butter', 'eggs'],
                'proteins' => ['chicken', 'beef', 'lamb', 'seafood'],
                'dry_goods' => ['rice', 'pasta', 'flour', 'oil']
            ],
            'Cafe' => [
                'vegetables' => [
                    'salad_items' => ['lettuce', 'tomatoes', 'cucumber', 'avocado'],
                    'sandwich_items' => ['tomatoes', 'lettuce', 'onions', 'sprouts'],
                    'breakfast_items' => ['mushrooms', 'spinach', 'tomatoes']
                ],
                'fruits' => ['berries', 'bananas', 'seasonal_fruits'],
                'dairy' => ['milk', 'alternative_milks', 'cheese', 'yogurt', 'eggs'],
                'bakery' => ['bread', 'pastries', 'muffins'],
                'beverages' => ['coffee_beans', 'tea', 'juices']
            ],
            'Hotel' => [
                'vegetables' => [
                    'bulk_staples' => ['tomatoes', 'onions', 'potatoes', 'carrots'],
                    'salad_bar' => ['lettuce', 'cucumber', 'capsicum', 'tomatoes'],
                    'garnishes' => ['parsley', 'lemon', 'cherry_tomatoes']
                ],
                'fruits' => ['seasonal_fruits', 'citrus', 'melons'],
                'breakfast' => ['eggs', 'bacon', 'sausages', 'mushrooms', 'tomatoes'],
                'large_volume' => true
            ],
            'Supermarket' => [
                'vegetables' => [
                    'full_range' => ['all_vegetables', 'including_okra', 'specialty_items'],
                    'high_volume' => true,
                    'daily_delivery' => true
                ],
                'direct_to_consumer' => true
            ],
            'Bar' => [
                'vegetables' => [
                    'garnishes' => ['lemons', 'limes', 'celery', 'olives'],
                    'bar_snacks' => ['cherry_tomatoes', 'cucumber']
                ],
                'limited_fresh' => true
            ],
            'Bakery' => [
                'vegetables' => [
                    'limited' => ['tomatoes_for_sandwiches', 'lettuce'],
                ],
                'fruits' => ['berries', 'seasonal_for_tarts'],
                'dairy' => ['eggs', 'milk', 'cream', 'butter'],
                'dry_goods' => ['flour', 'sugar', 'yeast']
            ]
        ];

        // Check for specific cuisine types that might need special items
        if ($this->isAsianCuisine($googleTypes)) {
            return array_merge($productMap['Restaurant'] ?? [], [
                'specialty_asian' => ['okra', 'bitter_melon', 'asian_greens', 'lemongrass']
            ]);
        }

        if ($this->isItalianCuisine($googleTypes)) {
            return array_merge($productMap['Restaurant'] ?? [], [
                'italian_essentials' => ['tomatoes', 'basil', 'garlic', 'olive_oil']
            ]);
        }

        return $productMap[$businessType] ?? $productMap['Restaurant'];
    }

    /**
     * Estimate procurement volumes based on business metrics
     */
    protected function estimateVolumes(array $businessData): array
    {
        $reviewCount = $businessData['google_reviews_count'] ?? 0;
        $priceLevel = $businessData['price_level'] ?? 2;
        $classification = $businessData['size_classification'] ?? 'MEDIUM';

        // Base estimates in kg per week
        $volumeMap = [
            'WHALE' => [
                'vegetables' => '500-1000kg',
                'fruits' => '200-400kg',
                'dairy' => '300-500L',
                'proteins' => '400-800kg',
                'frequency' => 'Daily',
                'min_order' => '$5000'
            ],
            'BIG' => [
                'vegetables' => '200-500kg',
                'fruits' => '100-200kg',
                'dairy' => '150-300L',
                'proteins' => '200-400kg',
                'frequency' => '3x per week',
                'min_order' => '$2000'
            ],
            'MEDIUM' => [
                'vegetables' => '50-200kg',
                'fruits' => '30-100kg',
                'dairy' => '50-150L',
                'proteins' => '50-200kg',
                'frequency' => '2x per week',
                'min_order' => '$500'
            ],
            'SMALL' => [
                'vegetables' => '20-50kg',
                'fruits' => '10-30kg',
                'dairy' => '20-50L',
                'proteins' => '20-50kg',
                'frequency' => 'Weekly',
                'min_order' => '$200'
            ]
        ];

        return $volumeMap[$classification] ?? $volumeMap['MEDIUM'];
    }

    /**
     * Identify pain points that we can solve
     */
    protected function identifyPainPoints(array $businessData): array
    {
        $painPoints = [];
        $priceLevel = $businessData['price_level'] ?? 2;
        $classification = $businessData['size_classification'] ?? 'MEDIUM';

        // Price-conscious businesses
        if ($priceLevel <= 2) {
            $painPoints[] = [
                'type' => 'cost_sensitive',
                'description' => 'Looking for better prices on bulk orders',
                'solution' => 'Direct from market pricing, no middleman fees'
            ];
        }

        // High-volume businesses
        if (in_array($classification, ['WHALE', 'BIG'])) {
            $painPoints[] = [
                'type' => 'reliable_supply',
                'description' => 'Needs consistent quality and availability',
                'solution' => 'Guaranteed stock levels and quality control'
            ];
            $painPoints[] = [
                'type' => 'delivery_scheduling',
                'description' => 'Complex delivery requirements',
                'solution' => 'Flexible delivery windows, even early morning'
            ];
        }

        // Small businesses
        if ($classification === 'SMALL') {
            $painPoints[] = [
                'type' => 'minimum_orders',
                'description' => 'Struggling with large minimum orders',
                'solution' => 'Lower minimums for small businesses'
            ];
            $painPoints[] = [
                'type' => 'payment_terms',
                'description' => 'Cash flow constraints',
                'solution' => 'Flexible payment terms, 14-30 days'
            ];
        }

        // All restaurants have these pain points
        $painPoints[] = [
            'type' => 'freshness',
            'description' => 'Need fresher produce',
            'solution' => 'Direct from Sydney Markets, same-day delivery'
        ];

        $painPoints[] = [
            'type' => 'ordering_convenience',
            'description' => 'Time-consuming ordering process',
            'solution' => 'Quick online ordering, saved order templates'
        ];

        return $painPoints;
    }

    /**
     * Create a targeted approach strategy for conversion
     */
    protected function createApproachStrategy(array $businessData, array $products, array $painPoints): array
    {
        $strategy = [
            'opening_line' => $this->generateOpeningLine($businessData, $painPoints),
            'value_proposition' => $this->generateValueProp($businessData, $painPoints),
            'specific_products' => $this->highlightSpecificProducts($products),
            'call_to_action' => $this->generateCTA($businessData),
            'follow_up_sequence' => $this->createFollowUpSequence($businessData)
        ];

        return $strategy;
    }

    /**
     * Generate personalized opening line for outreach
     */
    protected function generateOpeningLine(array $businessData, array $painPoints): string
    {
        $name = $businessData['business_name'];
        $rating = $businessData['google_rating'] ?? null;

        $templates = [
            'high_rated' => "Hi {$name} team! With your {$rating}★ rating, you clearly care about quality - we supply Sydney's top restaurants with premium produce direct from the markets.",
            'cost_sensitive' => "Hi {$name}! We help restaurants like yours save 20-30% on fresh produce with direct market access - no middleman markups.",
            'high_volume' => "Hi {$name} team! We specialize in reliable, high-volume supply for busy restaurants - guaranteed daily delivery from Sydney Markets.",
            'small_business' => "Hi {$name}! We work with local restaurants to provide flexible ordering with no crazy minimums - perfect for independent venues."
        ];

        if ($rating && $rating >= 4.5) {
            return $templates['high_rated'];
        }

        $mainPainPoint = $painPoints[0]['type'] ?? 'cost_sensitive';
        return $templates[$mainPainPoint] ?? $templates['cost_sensitive'];
    }

    /**
     * Generate value proposition based on business needs
     */
    protected function generateValueProp(array $businessData, array $painPoints): array
    {
        return [
            'headline' => 'Fresh Produce Direct from Sydney Markets',
            'benefits' => [
                '20-30% savings vs traditional suppliers',
                'Same-day delivery from market to kitchen',
                'No minimum orders for small businesses',
                'Quality guarantee on all produce',
                '24/7 online ordering platform'
            ],
            'proof' => 'Trusted by 200+ Sydney restaurants'
        ];
    }

    /**
     * Highlight specific products they likely need
     */
    protected function highlightSpecificProducts(array $products): string
    {
        $vegetables = $products['vegetables'] ?? [];

        $highlights = [];
        if (isset($vegetables['tomatoes'])) {
            $highlights[] = "premium tomatoes (roma, cherry, heirloom)";
        }
        if (isset($vegetables['specialty'])) {
            $highlights[] = "specialty items like okra and asian vegetables";
        }
        if (isset($vegetables['herbs'])) {
            $highlights[] = "fresh herbs daily";
        }

        return "We stock everything you need: " . implode(', ', $highlights);
    }

    /**
     * Generate call to action
     */
    protected function generateCTA(array $businessData): string
    {
        $classification = $businessData['size_classification'] ?? 'MEDIUM';

        $ctas = [
            'WHALE' => 'Schedule a meeting with our enterprise accounts team',
            'BIG' => 'Get a custom quote based on your volume needs',
            'MEDIUM' => 'Try us with your next order - get 20% off first delivery',
            'SMALL' => 'Start small - no minimums, order just what you need'
        ];

        return $ctas[$classification] ?? $ctas['MEDIUM'];
    }

    /**
     * Create follow-up sequence
     */
    protected function createFollowUpSequence(array $businessData): array
    {
        return [
            'day_1' => 'Initial outreach email/call',
            'day_3' => 'Follow up with menu analysis and suggested products',
            'day_7' => 'Share case study of similar restaurant saving money',
            'day_14' => 'Offer free sample delivery',
            'day_21' => 'Final offer with special discount',
            'day_30' => 'Add to nurture campaign'
        ];
    }

    /**
     * Calculate likelihood of conversion
     */
    protected function calculateConversionScore(array $businessData, array $painPoints): int
    {
        $score = 50; // Base score

        // Higher score for cost-sensitive businesses
        if ($businessData['price_level'] <= 2) {
            $score += 20;
        }

        // Higher score for medium-sized (sweet spot)
        if ($businessData['size_classification'] === 'MEDIUM') {
            $score += 15;
        }

        // Higher score if multiple pain points
        if (count($painPoints) >= 3) {
            $score += 15;
        }

        // Lower score for very high-end (likely has established suppliers)
        if ($businessData['price_level'] >= 4) {
            $score -= 20;
        }

        return min(100, $score);
    }

    /**
     * Get best time to contact based on business type
     */
    protected function getBestContactTime(string $businessType): array
    {
        $contactTimes = [
            'Restaurant' => [
                'best_days' => ['Tuesday', 'Wednesday', 'Thursday'],
                'best_time' => '2:00 PM - 4:00 PM', // Between lunch and dinner
                'avoid' => 'Friday-Sunday, 11am-2pm, 6pm-10pm'
            ],
            'Cafe' => [
                'best_days' => ['Tuesday', 'Wednesday', 'Thursday'],
                'best_time' => '10:00 AM - 11:00 AM or 2:00 PM - 3:00 PM',
                'avoid' => 'Early morning (6am-9am), Weekends'
            ],
            'Hotel' => [
                'best_days' => ['Monday', 'Tuesday', 'Wednesday'],
                'best_time' => '10:00 AM - 12:00 PM',
                'avoid' => 'Check-in/out times (2pm-4pm)'
            ],
            'Bar' => [
                'best_days' => ['Monday', 'Tuesday', 'Wednesday'],
                'best_time' => '2:00 PM - 5:00 PM',
                'avoid' => 'Thursday-Saturday nights'
            ]
        ];

        return $contactTimes[$businessType] ?? $contactTimes['Restaurant'];
    }

    /**
     * Identify decision makers
     */
    protected function getDecisionMakers(array $businessData): array
    {
        $classification = $businessData['size_classification'] ?? 'MEDIUM';

        $decisionMakers = [
            'WHALE' => [
                'primary' => 'Procurement Manager / F&B Director',
                'secondary' => 'Executive Chef',
                'approach' => 'Formal RFQ process, focus on reliability and scale'
            ],
            'BIG' => [
                'primary' => 'Operations Manager / Owner',
                'secondary' => 'Head Chef',
                'approach' => 'Professional presentation, ROI focused'
            ],
            'MEDIUM' => [
                'primary' => 'Owner / Restaurant Manager',
                'secondary' => 'Head Chef',
                'approach' => 'Relationship-based, value and service focused'
            ],
            'SMALL' => [
                'primary' => 'Owner-Operator',
                'secondary' => 'Chef (often the same person)',
                'approach' => 'Personal touch, flexibility emphasized'
            ]
        ];

        return $decisionMakers[$classification] ?? $decisionMakers['MEDIUM'];
    }

    /**
     * Get procurement patterns
     */
    protected function getProcurementPatterns(string $businessType): array
    {
        $patterns = [
            'Restaurant' => [
                'ordering_days' => ['Monday', 'Thursday'],
                'delivery_preference' => 'Early morning (6-8am)',
                'payment_terms' => '30 days',
                'order_method' => 'Online or phone',
                'special_needs' => 'Consistent quality, reliable supply'
            ],
            'Cafe' => [
                'ordering_days' => ['Tuesday', 'Friday'],
                'delivery_preference' => 'Early morning (5-7am)',
                'payment_terms' => '14 days',
                'order_method' => 'Online preferred',
                'special_needs' => 'Small quantities, high quality'
            ],
            'Hotel' => [
                'ordering_days' => ['Daily'],
                'delivery_preference' => 'Multiple times per day',
                'payment_terms' => '60 days',
                'order_method' => 'EDI/API integration',
                'special_needs' => 'Volume consistency, compliance docs'
            ]
        ];

        return $patterns[$businessType] ?? $patterns['Restaurant'];
    }

    /**
     * Check if Asian cuisine
     */
    protected function isAsianCuisine(array $googleTypes): bool
    {
        $asianTypes = ['asian_restaurant', 'chinese_restaurant', 'japanese_restaurant',
                      'thai_restaurant', 'vietnamese_restaurant', 'korean_restaurant'];

        return !empty(array_intersect($googleTypes, $asianTypes));
    }

    /**
     * Check if Italian cuisine
     */
    protected function isItalianCuisine(array $googleTypes): bool
    {
        $italianTypes = ['italian_restaurant', 'pizza_restaurant'];

        return !empty(array_intersect($googleTypes, $italianTypes));
    }
}