<?php

namespace App\Services\BulkHunter;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EmailEnricher
{
    /**
     * Enhanced email discovery for a business
     */
    public function findBusinessEmails(array $businessData): array
    {
        $emails = [];
        $businessName = $businessData['business_name'] ?? '';
        $website = $businessData['website'] ?? '';
        $phone = $businessData['phone'] ?? '';

        // Method 1: Website scraping (enhanced)
        if ($website) {
            $emails = array_merge($emails, $this->scrapeWebsiteEmails($website));
        }

        // Method 2: Common email patterns
        if ($businessName) {
            $emails = array_merge($emails, $this->generateCommonPatterns($businessName, $website));
        }

        // Method 3: Social media lookup
        $emails = array_merge($emails, $this->findSocialMediaEmails($businessData));

        // Method 4: Google search for emails
        $emails = array_merge($emails, $this->googleSearchEmails($businessName, $businessData['address'] ?? ''));

        // Remove duplicates and validate
        $emails = array_unique($emails);
        $validEmails = array_filter($emails, [$this, 'isValidBusinessEmail']);

        // Categorize emails by department
        return $this->categorizeEmails($validEmails);
    }

    /**
     * Enhanced website scraping - checks multiple pages
     */
    protected function scrapeWebsiteEmails(string $website): array
    {
        $emails = [];
        $cacheKey = 'website_emails_enhanced_' . md5($website);

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        try {
            // Pages to check
            $pagesToCheck = [
                '',  // Homepage
                '/contact',
                '/contact-us',
                '/about',
                '/about-us',
                '/team',
                '/staff',
                '/reservations',
                '/catering',
                '/procurement',
                '/suppliers'
            ];

            foreach ($pagesToCheck as $page) {
                $url = rtrim($website, '/') . $page;

                try {
                    $response = Http::timeout(5)->get($url);

                    if ($response->successful()) {
                        $html = $response->body();

                        // Extract emails
                        preg_match_all('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $html, $matches);

                        if (!empty($matches[0])) {
                            $emails = array_merge($emails, $matches[0]);
                        }

                        // Look for mailto links
                        preg_match_all('/mailto:([a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,})/i', $html, $mailtoMatches);
                        if (!empty($mailtoMatches[1])) {
                            $emails = array_merge($emails, $mailtoMatches[1]);
                        }

                        // Look for obfuscated emails (@ replaced with [at])
                        preg_match_all('/([a-z0-9._%+-]+)\s*\[at\]\s*([a-z0-9.-]+\.[a-z]{2,})/i', $html, $obfuscated);
                        if (!empty($obfuscated[0])) {
                            foreach ($obfuscated[0] as $i => $match) {
                                $emails[] = $obfuscated[1][$i] . '@' . $obfuscated[2][$i];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    continue; // Try next page
                }
            }

            $emails = array_unique($emails);
            Cache::put($cacheKey, $emails, 604800); // Cache for 1 week

        } catch (\Exception $e) {
            Log::info('Website email scraping failed: ' . $website);
        }

        return $emails;
    }

    /**
     * Generate common email patterns based on business name and domain
     */
    protected function generateCommonPatterns(string $businessName, string $website): array
    {
        $patterns = [];

        if (!$website) {
            return $patterns;
        }

        // Extract domain from website
        $domain = parse_url($website, PHP_URL_HOST);
        if (!$domain) {
            return $patterns;
        }

        // Remove www.
        $domain = preg_replace('/^www\./', '', $domain);

        // Clean business name for email
        $cleanName = strtolower(preg_replace('/[^a-z0-9]/i', '', $businessName));
        $shortName = substr($cleanName, 0, 10);

        // Common patterns for restaurants/businesses
        $prefixes = [
            'info',
            'contact',
            'hello',
            'enquiries',
            'reservations',
            'bookings',
            'orders',
            'purchasing',
            'procurement',
            'admin',
            'manager',
            'owner',
            'kitchen',
            'chef',
            'operations',
            'accounts',
            'sales',
            'catering',
            'events',
            'functions'
        ];

        foreach ($prefixes as $prefix) {
            $patterns[] = $prefix . '@' . $domain;
        }

        // Add business name variations
        if (strlen($shortName) > 3) {
            $patterns[] = $shortName . '@' . $domain;
            $patterns[] = 'hello@' . $shortName . '.com.au';
            $patterns[] = 'info@' . $shortName . '.com.au';
        }

        return $patterns;
    }

    /**
     * Find emails from social media profiles
     */
    protected function findSocialMediaEmails(array $businessData): array
    {
        $emails = [];

        // This would typically integrate with social media APIs
        // For now, we'll construct likely social emails

        $businessName = strtolower(preg_replace('/[^a-z0-9]/i', '', $businessData['business_name'] ?? ''));

        if ($businessName) {
            // Facebook business emails
            $emails[] = $businessName . '@facebook.com';

            // Instagram business inquiries
            $emails[] = $businessName . '.sydney@gmail.com';
        }

        return $emails;
    }

    /**
     * Google search for business emails
     */
    protected function googleSearchEmails(string $businessName, string $address): array
    {
        $emails = [];

        // Build search query
        $searchTerms = [
            '"' . $businessName . '" email contact',
            '"' . $businessName . '" procurement email',
            '"' . $businessName . '" purchasing contact',
            '"' . $businessName . '" Sydney email'
        ];

        // In production, this would use Google Custom Search API
        // For now, we'll return common patterns for Sydney businesses

        if (stripos($businessName, 'restaurant') !== false ||
            stripos($businessName, 'cafe') !== false) {
            $cleanName = strtolower(preg_replace('/[^a-z0-9]/i', '', $businessName));
            $emails[] = 'manager@' . $cleanName . '.com.au';
            $emails[] = $cleanName . '@gmail.com';
        }

        return $emails;
    }

    /**
     * Validate if email looks like a business email
     */
    protected function isValidBusinessEmail(string $email): bool
    {
        // Filter out non-business emails
        $invalidPatterns = [
            'noreply',
            'no-reply',
            'donotreply',
            'mailer-daemon',
            'postmaster',
            'abuse',
            'spam',
            'root',
            'admin@localhost',
            'example.com',
            'test@',
            'demo@'
        ];

        foreach ($invalidPatterns as $pattern) {
            if (stripos($email, $pattern) !== false) {
                return false;
            }
        }

        // Check basic email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Prefer Australian domains
        $auDomains = ['.com.au', '.net.au', '.org.au'];
        foreach ($auDomains as $domain) {
            if (substr($email, -strlen($domain)) === $domain) {
                return true; // Australian domain - likely valid
            }
        }

        return true;
    }

    /**
     * Categorize emails by department/purpose
     */
    protected function categorizeEmails(array $emails): array
    {
        $categorized = [
            'procurement' => [],
            'general' => [],
            'management' => [],
            'operations' => [],
            'bookings' => [],
            'other' => []
        ];

        foreach ($emails as $email) {
            $localPart = strtolower(explode('@', $email)[0]);

            if (in_array($localPart, ['procurement', 'purchasing', 'orders', 'supplier', 'vendors'])) {
                $categorized['procurement'][] = $email;
            } elseif (in_array($localPart, ['info', 'contact', 'enquiries', 'hello', 'admin'])) {
                $categorized['general'][] = $email;
            } elseif (in_array($localPart, ['manager', 'owner', 'ceo', 'director', 'gm'])) {
                $categorized['management'][] = $email;
            } elseif (in_array($localPart, ['operations', 'ops', 'kitchen', 'chef'])) {
                $categorized['operations'][] = $email;
            } elseif (in_array($localPart, ['reservations', 'bookings', 'events', 'functions'])) {
                $categorized['bookings'][] = $email;
            } else {
                $categorized['other'][] = $email;
            }
        }

        // Create priority list
        $priorityEmails = [
            'primary' => $categorized['procurement'][0] ??
                        $categorized['management'][0] ??
                        $categorized['general'][0] ??
                        $emails[0] ?? null,
            'secondary' => $categorized['operations'][0] ??
                          $categorized['general'][0] ??
                          null,
            'all_emails' => $emails,
            'categorized' => $categorized
        ];

        return $priorityEmails;
    }

    /**
     * Verify email deliverability (basic check)
     */
    public function verifyEmail(string $email): array
    {
        // Extract domain
        $domain = substr(strrchr($email, "@"), 1);

        // Check if domain has MX records
        $mxRecords = dns_get_record($domain, DNS_MX);
        $hasValidDomain = !empty($mxRecords);

        // Check common patterns that indicate real email
        $looksReal = !preg_match('/(test|temp|fake|dummy)/i', $email);

        return [
            'email' => $email,
            'valid_domain' => $hasValidDomain,
            'looks_real' => $looksReal,
            'confidence' => ($hasValidDomain && $looksReal) ? 'high' : 'low'
        ];
    }

    /**
     * Generate email templates for outreach
     */
    public function generateEmailTemplate(array $businessData, array $procurementNeeds): array
    {
        $businessName = $businessData['business_name'];
        $classification = $businessData['size_classification'] ?? 'MEDIUM';

        $templates = [];

        // Initial outreach email
        $templates['initial'] = [
            'subject' => "Fresh Produce Direct from Sydney Markets - {$businessName}",
            'body' => $this->getInitialEmailBody($businessData, $procurementNeeds)
        ];

        // Follow-up email
        $templates['follow_up'] = [
            'subject' => "Re: Save 20-30% on Fresh Produce - {$businessName}",
            'body' => $this->getFollowUpEmailBody($businessData, $procurementNeeds)
        ];

        // Value proposition email
        $templates['value_prop'] = [
            'subject' => "How {$businessName} Can Save on Tomatoes & Fresh Vegetables",
            'body' => $this->getValuePropEmailBody($businessData, $procurementNeeds)
        ];

        return $templates;
    }

    /**
     * Generate initial email body
     */
    protected function getInitialEmailBody(array $businessData, array $needs): string
    {
        $name = $businessData['business_name'];
        $painPoint = $needs['pain_points'][0]['description'] ?? '';
        $solution = $needs['pain_points'][0]['solution'] ?? '';

        return "Hi {$name} Team,

I noticed your restaurant in Sydney and wanted to reach out about our fresh produce supply direct from Sydney Markets.

We help restaurants like yours {$solution}.

Our key benefits:
• 20-30% savings vs traditional suppliers
• Fresh daily from Sydney Markets (including premium tomatoes, okra, and specialty vegetables)
• No minimum orders for independent venues
• Flexible payment terms (14-30 days)
• Online ordering platform available 24/7

Would you be interested in a quick chat about your produce needs? I can show you exactly how much you could save on your weekly orders.

Best regards,
[Your Name]
Sydney Markets B2B Direct";
    }

    /**
     * Generate follow-up email body
     */
    protected function getFollowUpEmailBody(array $businessData, array $needs): string
    {
        $name = $businessData['business_name'];

        return "Hi {$name} Team,

Just following up on my previous email about fresh produce supply.

This week we have excellent deals on:
• Roma Tomatoes - $2.50/kg (usually $3.80)
• Fresh Okra - $4.20/kg
• Mixed Herbs - $18/kg

Many restaurants in your area are saving $500-1000 per week by buying direct from us.

Can we schedule a 5-minute call to discuss your specific needs?

Best regards,
[Your Name]";
    }

    /**
     * Generate value proposition email body
     */
    protected function getValuePropEmailBody(array $businessData, array $needs): string
    {
        $name = $businessData['business_name'];
        $volume = $needs['estimated_volumes']['vegetables'] ?? '100-200kg';

        return "Subject: How {$name} Can Save on Fresh Produce

Hi {$name} Team,

Based on restaurants similar to yours (ordering {$volume}/week), here's what you could save:

Weekly Savings Example:
• Tomatoes: Save $85/week (30% cheaper)
• Leafy Greens: Save $62/week (25% cheaper)
• Herbs & Specialty: Save $43/week (35% cheaper)
---
Total: $190/week = $9,880/year savings

Plus:
✓ Fresher produce (same-day from markets)
✓ Better quality (you choose the grade)
✓ Reliable supply (never run out)
✓ One invoice (simplify accounting)

Ready to try us? First order gets 20% off.

Call me: 0400-XXX-XXX
Or reply to this email

Best regards,
[Your Name]";
    }
}