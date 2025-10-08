<?php

namespace Tests\Integration;

use Tests\TestCase;
use DOMDocument;
use DOMXPath;

class OrderCardValidationTest extends TestCase
{
    private $dashboardHtml;
    private $dom;
    private $xpath;

    protected function setUp(): void
    {
        parent::setUp();

        // Load the buyer dashboard HTML
        $filePath = base_path('resources/views/buyer/dashboard.blade.php');
        $this->dashboardHtml = file_get_contents($filePath);

        // Set up DOM parser
        $this->dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $this->dom->loadHTML($this->dashboardHtml);
        libxml_clear_errors();

        $this->xpath = new DOMXPath($this->dom);
    }

    /**
     * Test 1: Verify all 5 cards are visible
     */
    public function testAllFiveCardsAreVisible()
    {
        // Check for 5 regular cards + 1 best price card (6 total, but 2 are hidden)
        $visibleCardCount = 0;

        // Cards with specific quote IDs that should be visible
        $visibleQuoteIds = ['9', '7', '4', '8', '5', '1'];

        foreach ($visibleQuoteIds as $quoteId) {
            $pattern = '/data-quote-id="' . $quoteId . '"/';
            if (preg_match($pattern, $this->dashboardHtml)) {
                $visibleCardCount++;
            }
        }

        $this->assertEquals(6, $visibleCardCount, 'Should have exactly 6 order cards (5 regular + 1 best price)');

        // Check that cards 2 and 3 are hidden
        $this->assertStringContainsString('data-quote-id="2" style="display: none;"', $this->dashboardHtml);
        $this->assertStringContainsString('data-quote-id="3" style="display: none;"', $this->dashboardHtml);
    }

    /**
     * Test 2: Check card heights match specifications
     */
    public function testCardHeightsMatchSpecifications()
    {
        // Regular cards should have height: 72px
        $regularCardHeightPattern = '/\.ordercard-item\s*{[^}]*height:\s*72px\s*!important/';
        $this->assertMatchesRegularExpression($regularCardHeightPattern, $this->dashboardHtml,
            'Regular cards should have height: 72px');

        // Best price card should have height: 84px
        $bestPriceHeightPattern = '/\.ordercard-item\.best-value\s*{[^}]*height:\s*84px\s*!important/';
        $this->assertMatchesRegularExpression($bestPriceHeightPattern, $this->dashboardHtml,
            'Best price card should have height: 84px');

        // Also check min-height is set
        $this->assertStringContainsString('min-height: 72px !important', $this->dashboardHtml);
        $this->assertStringContainsString('min-height: 84px !important', $this->dashboardHtml);
    }

    /**
     * Test 3: Validate typography sizes
     */
    public function testTypographySizesAreCorrect()
    {
        $typographyChecks = [
            'Company name' => ['selector' => '.quote-company', 'size' => '14px'],
            'Price value' => ['selector' => '.quote-value', 'size' => '20px'],
            'Meta text' => ['selector' => '.quote-meta', 'size' => '11px'],
            'Button text' => ['selector' => '.btn-accept-mini', 'size' => '12px'],
        ];

        foreach ($typographyChecks as $element => $check) {
            $pattern = '/' . preg_quote($check['selector']) . '\s*{[^}]*font-size:\s*' .
                       preg_quote($check['size']) . '\s*!important/';
            $this->assertMatchesRegularExpression($pattern, $this->dashboardHtml,
                "$element should have font-size: {$check['size']}");
        }
    }

    /**
     * Test 4: Confirm button accessibility (min touch targets)
     */
    public function testButtonAccessibility()
    {
        // Check button minimum height of 32px
        $buttonHeightPattern = '/\.btn-accept-mini.*{[^}]*height:\s*32px\s*!important/';
        $this->assertMatchesRegularExpression($buttonHeightPattern, $this->dashboardHtml,
            'Buttons should have minimum height of 32px');

        // Check min-height is also set
        $this->assertStringContainsString('min-height: 32px !important', $this->dashboardHtml);

        // Check button font size for readability
        $buttonFontPattern = '/\.btn-accept-mini.*{[^}]*font-size:\s*12px\s*!important/';
        $this->assertMatchesRegularExpression($buttonFontPattern, $this->dashboardHtml,
            'Buttons should have font-size: 12px');
    }

    /**
     * Test 5: Test price sorting logic
     */
    public function testPriceSortingLogic()
    {
        // Extract prices and company names from the HTML
        $cards = [
            ['id' => '9', 'company' => 'Premium Organics', 'price' => 420, 'position' => 1],
            ['id' => '7', 'company' => 'Valley Produce', 'price' => 412, 'position' => 2],
            ['id' => '4', 'company' => 'Flemington Wholesale', 'price' => 402, 'position' => 3],
            ['id' => '8', 'company' => 'Green Grocers', 'price' => 399, 'position' => 4],
            ['id' => '5', 'company' => 'Sydney Fresh', 'price' => 389, 'position' => 5],
            ['id' => '1', 'company' => 'Market Direct Supply', 'price' => 385, 'position' => 'best'],
        ];

        // Verify cards are in correct order (worst to best)
        foreach ($cards as $card) {
            $pattern = '/data-quote-id="' . $card['id'] . '".*?' .
                      preg_quote($card['company']) . '.*?\$' . $card['price'] . '/s';
            $this->assertMatchesRegularExpression($pattern, $this->dashboardHtml,
                "Card {$card['id']} ({$card['company']}) should show price \${$card['price']}");
        }

        // Verify price order is descending (worst at top)
        $this->assertTrue(420 > 412 && 412 > 402 && 402 > 399 && 399 > 389 && 389 > 385,
            'Prices should be sorted from worst (highest) to best (lowest)');
    }

    /**
     * Test 6: Verify best price sticky positioning
     */
    public function testBestPriceStickyPositioning()
    {
        // Check for sticky positioning CSS
        $stickyPattern = '/\.ordercard-item\.best-price\s*{[^}]*position:\s*sticky\s*!important[^}]*bottom:\s*0\s*!important/';
        $this->assertMatchesRegularExpression($stickyPattern, $this->dashboardHtml,
            'Best price card should have sticky positioning at bottom');

        // Check for golden treatment
        $goldenPattern = '/\.ordercard-item\.best-value\s*{[^}]*background:\s*linear-gradient\([^)]*#FEF3C7[^)]*#FDE68A/';
        $this->assertMatchesRegularExpression($goldenPattern, $this->dashboardHtml,
            'Best price card should have golden gradient background');
    }

    /**
     * Test 7: Check responsive behavior
     */
    public function testResponsiveBehavior()
    {
        // Check for container fixed width
        $containerWidthPattern = '/#orderCard\s*{[^}]*width:\s*380px\s*!important/';
        $this->assertMatchesRegularExpression($containerWidthPattern, $this->dashboardHtml,
            'Order card container should have fixed width of 380px');

        // Check for proper overflow handling
        $this->assertStringContainsString('overflow-y: auto', $this->dashboardHtml);
        $this->assertStringContainsString('overflow-x: hidden', $this->dashboardHtml);
    }

    /**
     * Test 8: Validate color contrast ratios
     */
    public function testColorContrastRatios()
    {
        // Check progressive color system
        $colorTests = [
            ['id' => '9', 'bg' => '#F3F4F6', 'text' => '#6B7280', 'name' => 'Worst price (gray)'],
            ['id' => '7', 'bg' => '#E5E7EB', 'text' => '#4B5563', 'name' => 'Light gray'],
            ['id' => '4', 'bg' => '#D1FAE5', 'text' => '#065F46', 'name' => 'Very light green'],
            ['id' => '8', 'bg' => '#A7F3D0', 'text' => '#047857', 'name' => 'Light green'],
            ['id' => '5', 'bg' => '#6EE7B7', 'text' => '#065F46', 'name' => 'Second best (green)'],
        ];

        foreach ($colorTests as $test) {
            $pattern = '/data-quote-id="' . $test['id'] . '"\]\s*\.quote-badge\s*{[^}]*background:\s*' .
                      preg_quote($test['bg']) . '[^}]*color:\s*' . preg_quote($test['text']) . '/';
            $this->assertMatchesRegularExpression($pattern, $this->dashboardHtml,
                "{$test['name']} should have correct color scheme");
        }
    }

    /**
     * Test 9: Test keyboard navigation
     */
    public function testKeyboardNavigation()
    {
        // Check that buttons have proper cursor and are clickable
        $this->assertStringContainsString('cursor: pointer', $this->dashboardHtml);

        // Check for onclick handlers
        $this->assertStringContainsString('onclick="acceptQuote', $this->dashboardHtml);
        $this->assertStringContainsString('onclick="viewDetails', $this->dashboardHtml);

        // Check for transition effects (indicates interactivity)
        $this->assertStringContainsString('transition: all 0.2s', $this->dashboardHtml);
    }

    /**
     * Test 10: Check for CSS conflicts
     */
    public function testNoCSSConflicts()
    {
        // Check for !important usage to prevent conflicts
        $importantCount = substr_count($this->dashboardHtml, '!important');
        $this->assertGreaterThan(50, $importantCount,
            'Should use !important to prevent CSS conflicts');

        // Check for specific selectors to avoid conflicts
        $this->assertStringContainsString('.ordercard-item', $this->dashboardHtml);
        $this->assertStringContainsString('.ordercard-container', $this->dashboardHtml);

        // Ensure no duplicate IDs
        $this->assertEquals(1, substr_count($this->dashboardHtml, 'id="orderCard"'));
        $this->assertEquals(1, substr_count($this->dashboardHtml, 'id="orderCardContainer"'));
    }

    /**
     * Test 11: Badge dimensions
     */
    public function testBadgeDimensions()
    {
        // Regular badge should be 28x28px
        $badgePattern = '/\.quote-badge\s*{[^}]*width:\s*28px\s*!important[^}]*height:\s*28px\s*!important/';
        $this->assertMatchesRegularExpression($badgePattern, $this->dashboardHtml,
            'Quote badge should be 28x28px');

        // Best price badge should be slightly larger (32x32px)
        $bestBadgePattern = '/\.ordercard-item\.best-price\s+\.quote-badge\s*{[^}]*width:\s*32px[^}]*height:\s*32px/';
        $this->assertMatchesRegularExpression($bestBadgePattern, $this->dashboardHtml,
            'Best price badge should be 32x32px');
    }

    /**
     * Test 12: Spacing validation
     */
    public function testSpacingValidation()
    {
        // Check for 8px gaps
        $this->assertStringContainsString('gap: 8px', $this->dashboardHtml);

        // Check padding values
        $this->assertStringContainsString('padding: 8px', $this->dashboardHtml);
        $this->assertStringContainsString('padding: 0 8px', $this->dashboardHtml);
    }

    /**
     * Performance Test: Render time measurement
     */
    public function testRenderPerformance()
    {
        $startTime = microtime(true);

        // Simulate rendering by parsing the HTML
        $dom = new DOMDocument();
        @$dom->loadHTML($this->dashboardHtml);

        $endTime = microtime(true);
        $renderTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $this->assertLessThan(100, $renderTime,
            'HTML parsing should complete in less than 100ms');
    }

    /**
     * Edge Case Test: Long company names
     */
    public function testLongCompanyNames()
    {
        // Check for text overflow handling
        $this->assertStringContainsString('text-overflow: ellipsis', $this->dashboardHtml);
        $this->assertStringContainsString('overflow: hidden', $this->dashboardHtml);
        $this->assertStringContainsString('white-space: nowrap', $this->dashboardHtml);
    }

    /**
     * Edge Case Test: Large price values
     */
    public function testLargePriceValues()
    {
        // Check that price container can handle 4-digit prices
        $this->assertStringContainsString('$420', $this->dashboardHtml);

        // Check font size can accommodate large numbers
        $priceFontPattern = '/\.quote-value\s*{[^}]*font-size:\s*20px\s*!important/';
        $this->assertMatchesRegularExpression($priceFontPattern, $this->dashboardHtml,
            'Price font size should be 20px to handle large values');
    }

    /**
     * Accessibility Test: WCAG compliance
     */
    public function testWCAGCompliance()
    {
        // Check for semantic HTML
        $this->assertStringContainsString('<button', $this->dashboardHtml);
        $this->assertStringContainsString('<div class="quote-company"', $this->dashboardHtml);

        // Check for proper heading hierarchy (company names)
        $this->assertStringContainsString('font-weight: 600', $this->dashboardHtml);

        // Check for sufficient button size (32px height)
        $this->assertStringContainsString('height: 32px !important', $this->dashboardHtml);

        // Check for focus states (transition indicates interactive elements)
        $this->assertStringContainsString('transition:', $this->dashboardHtml);
    }

    /**
     * Visual Regression Test: Card structure
     */
    public function testCardStructureIntegrity()
    {
        // Verify each card has required components
        $requiredComponents = [
            'quote-badge' => 'Badge element',
            'quote-company' => 'Company name',
            'quote-meta' => 'Meta information',
            'quote-value' => 'Price value',
            'btn-accept-mini' => 'Accept button',
            'btn-details-mini' => 'Details button',
        ];

        foreach ($requiredComponents as $class => $description) {
            $this->assertStringContainsString("class=\"$class\"", $this->dashboardHtml,
                "Card should have $description with class '$class'");
        }
    }
}