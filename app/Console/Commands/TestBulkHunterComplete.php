<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BulkHunter\BulkHunterService;
use App\Services\BulkHunter\ProcurementAnalyzer;
use App\Services\BulkHunter\EmailEnricher;

class TestBulkHunterComplete extends Command
{
    protected $signature = 'bulkhunter:test-complete
                            {--keyword=restaurant : Business type to search}
                            {--location=Sydney : Location to search}
                            {--analyze : Run procurement analysis}
                            {--emails : Find email contacts}';

    protected $description = 'Test complete BulkHunter workflow with procurement analysis';

    protected $bulkHunterService;
    protected $procurementAnalyzer;
    protected $emailEnricher;

    public function __construct(
        BulkHunterService $bulkHunterService,
        ProcurementAnalyzer $procurementAnalyzer,
        EmailEnricher $emailEnricher
    ) {
        parent::__construct();
        $this->bulkHunterService = $bulkHunterService;
        $this->procurementAnalyzer = $procurementAnalyzer;
        $this->emailEnricher = $emailEnricher;
    }

    public function handle()
    {
        $keyword = $this->option('keyword');
        $location = $this->option('location');
        $runAnalysis = $this->option('analyze');
        $findEmails = $this->option('emails');

        $this->info("🔍 Starting BulkHunter Discovery for '{$keyword}' in {$location}");
        $this->newLine();

        // Discover businesses
        $businesses = $this->bulkHunterService->discoverRealBusinesses($keyword, $location);

        if (empty($businesses)) {
            $this->error('No businesses found. Check your API key and connection.');
            return 1;
        }

        $this->info("✅ Found " . count($businesses) . " businesses");
        $this->newLine();

        // Display first 3 businesses with full analysis
        $displayCount = min(3, count($businesses));

        for ($i = 0; $i < $displayCount; $i++) {
            $business = $businesses[$i];

            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("📍 Business #" . ($i + 1) . ": " . $business['business_name']);
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

            $this->table(
                ['Field', 'Value'],
                [
                    ['Type', $business['business_type'] ?? 'Restaurant'],
                    ['Address', $business['address'] ?? 'Address not available'],
                    ['Phone', $business['phone'] ?? 'Not available'],
                    ['Rating', ($business['google_rating'] ?? 'N/A') . ' ⭐ (' . ($business['google_reviews_count'] ?? 0) . ' reviews)'],
                    ['Price Level', str_repeat('$', $business['price_level'] ?? 2)],
                    ['Size', $business['size_classification'] ?? 'MEDIUM'],
                    ['ABN', $business['abn'] ?? 'Not found'],
                    ['Website', $business['website'] ?? 'Not available'],
                ]
            );

            // Procurement Analysis
            if ($runAnalysis) {
                $this->newLine();
                $this->comment("🔬 Procurement Analysis:");

                $needs = $this->procurementAnalyzer->analyzeProcurementNeeds($business);

                // Products needed
                $this->info("  📦 Likely Products Needed:");
                $allProducts = [];
                foreach ($needs['likely_products'] as $key => $value) {
                    if ($key === 'limited_fresh' || is_bool($value)) {
                        continue; // Skip boolean flags
                    }

                    if (is_array($value)) {
                        // Handle nested arrays
                        foreach ($value as $subKey => $subProducts) {
                            if (is_array($subProducts) && !empty($subProducts)) {
                                $allProducts = array_merge($allProducts, $subProducts);
                                $this->line("     • " . ucfirst(str_replace('_', ' ', $subKey)) . ": " . implode(', ', array_slice($subProducts, 0, 5)));
                            }
                        }
                    } elseif (is_string($value)) {
                        // Handle direct string values
                        $allProducts[] = $value;
                    }
                }

                if (empty($allProducts) && isset($needs['likely_products']['limited_fresh'])) {
                    $this->line("     • Limited fresh produce requirements for this business type");
                }

                // Weekly volumes
                $this->newLine();
                $this->info("  📊 Estimated Weekly Volumes:");
                foreach ($needs['estimated_volumes'] as $type => $volume) {
                    $this->line("     • " . ucfirst($type) . ": " . $volume);
                }

                // Pain points
                $this->newLine();
                $this->info("  ⚠️ Key Pain Points:");
                foreach ($needs['pain_points'] as $painPoint) {
                    $this->line("     • " . $painPoint['description']);
                    $this->comment("       💡 Solution: " . $painPoint['solution']);
                }

                // Conversion score
                $this->newLine();
                $score = $needs['conversion_score'];
                $rating = $score >= 80 ? 'Excellent' : ($score >= 60 ? 'Good' : ($score >= 40 ? 'Moderate' : 'Low'));
                $scoreColor = $score >= 80 ? 'info' : ($score >= 60 ? 'comment' : 'warn');
                $this->$scoreColor("  🎯 Conversion Score: {$score}/100 - {$rating} Conversion Potential");

                // Approach strategy
                $this->newLine();
                $this->info("  📋 Recommended Approach:");
                $strategy = $needs['approach_strategy'];
                if (is_array($strategy)) {
                    if (isset($strategy[0]) && !is_array($strategy[0])) {
                        // Simple array of strings
                        foreach ($strategy as $step) {
                            $this->line("     • " . (string)$step);
                        }
                    } else {
                        // Complex array structure - just show first valid string
                        $this->line("     " . json_encode($strategy, JSON_PRETTY_PRINT));
                    }
                } else {
                    $this->line("     " . (string)$strategy);
                }
            }

            // Email Discovery
            if ($findEmails) {
                $this->newLine();
                $this->comment("📧 Email Discovery:");

                $emailData = $this->emailEnricher->findBusinessEmails($business);

                if ($emailData['primary']) {
                    $this->info("  ✅ Primary Email: " . $emailData['primary']);
                }

                if ($emailData['secondary']) {
                    $this->line("  📨 Secondary Email: " . $emailData['secondary']);
                }

                // Show categorized emails
                if (!empty($emailData['categorized'])) {
                    $this->newLine();
                    $this->info("  📂 Emails by Department:");
                    foreach ($emailData['categorized'] as $dept => $emails) {
                        if (!empty($emails)) {
                            $this->line("     • " . ucfirst($dept) . ": " . implode(', ', array_slice($emails, 0, 2)));
                        }
                    }
                }

                // Verify primary email
                if ($emailData['primary']) {
                    $verification = $this->emailEnricher->verifyEmail($emailData['primary']);
                    $this->newLine();
                    $this->info("  ✔️ Email Verification:");
                    $this->line("     • Valid Domain: " . ($verification['valid_domain'] ? 'Yes' : 'No'));
                    $this->line("     • Confidence: " . ucfirst($verification['confidence']));
                }

                // Show sample outreach template
                if ($runAnalysis && $emailData['primary']) {
                    $this->newLine();
                    $this->info("  📝 Sample Outreach Email:");

                    $templates = $this->emailEnricher->generateEmailTemplate($business, $needs);
                    $initialTemplate = $templates['initial'];

                    $this->line("     Subject: " . $initialTemplate['subject']);
                    $this->newLine();
                    $this->comment("     Body Preview (first 3 lines):");
                    $lines = explode("\n", $initialTemplate['body']);
                    for ($j = 0; $j < min(3, count($lines)); $j++) {
                        if (!empty(trim($lines[$j]))) {
                            $this->line("     " . $lines[$j]);
                        }
                    }
                }
            }
        }

        $this->newLine();
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        // Summary statistics
        $this->info("📊 Discovery Summary:");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Businesses Found', count($businesses)],
                ['With ABN', count(array_filter($businesses, fn($b) => !empty($b['abn'])))],
                ['With Website', count(array_filter($businesses, fn($b) => !empty($b['website'])))],
                ['With Phone', count(array_filter($businesses, fn($b) => !empty($b['phone'])))],
                ['High Rating (4.0+)', count(array_filter($businesses, fn($b) => ($b['google_rating'] ?? 0) >= 4.0))],
            ]
        );

        if ($runAnalysis) {
            // Calculate average conversion score
            $totalScore = 0;
            $scoredCount = 0;
            foreach ($businesses as $business) {
                $needs = $this->procurementAnalyzer->analyzeProcurementNeeds($business);
                if ($needs['conversion_score'] > 0) {
                    $totalScore += $needs['conversion_score'];
                    $scoredCount++;
                }
            }

            if ($scoredCount > 0) {
                $avgScore = round($totalScore / $scoredCount, 1);
                $this->newLine();
                $this->info("🎯 Average Conversion Score: {$avgScore}/100");

                // Top products needed across all businesses
                $productFrequency = [];
                foreach ($businesses as $business) {
                    $needs = $this->procurementAnalyzer->analyzeProcurementNeeds($business);
                    foreach ($needs['likely_products'] as $key => $value) {
                        if ($key === 'limited_fresh' || is_bool($value)) {
                            continue;
                        }

                        if (is_array($value)) {
                            // Handle nested arrays
                            foreach ($value as $subKey => $subProducts) {
                                if (is_array($subProducts)) {
                                    foreach ($subProducts as $product) {
                                        if (is_string($product)) {
                                            $productFrequency[$product] = ($productFrequency[$product] ?? 0) + 1;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                arsort($productFrequency);
                $topProducts = array_slice($productFrequency, 0, 10, true);

                $this->newLine();
                $this->info("🥬 Top 10 Products in Demand:");
                foreach ($topProducts as $product => $frequency) {
                    $percentage = round(($frequency / count($businesses)) * 100);
                    $this->line("  • {$product}: {$percentage}% of businesses");
                }
            }
        }

        if ($findEmails) {
            // Email discovery statistics
            $withEmails = 0;
            foreach ($businesses as $business) {
                $emailData = $this->emailEnricher->findBusinessEmails($business);
                if (!empty($emailData['primary'])) {
                    $withEmails++;
                }
            }

            $emailRate = round(($withEmails / count($businesses)) * 100, 1);
            $this->newLine();
            $this->info("📧 Email Discovery Rate: {$emailRate}% ({$withEmails}/" . count($businesses) . ")");
        }

        $this->newLine();
        $this->info("✨ BulkHunter discovery complete!");

        return 0;
    }
}