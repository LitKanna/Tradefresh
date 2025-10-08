<?php

use App\Livewire\Quotes\BuyerQuotePanel;
use Livewire\Livewire;

test('buyer quote panel component renders successfully', function () {
    $component = Livewire::test(BuyerQuotePanel::class);

    $component->assertStatus(200);
})->skip('Database migrations needed');

test('buyer quote panel has correct namespace', function () {
    $reflection = new ReflectionClass(BuyerQuotePanel::class);

    expect($reflection->getNamespaceName())->toBe('App\Livewire\Quotes');
    expect($reflection->getShortName())->toBe('BuyerQuotePanel');
});

test('buyer quote panel has lazy loading attribute', function () {
    $reflection = new ReflectionClass(BuyerQuotePanel::class);
    $attributes = $reflection->getAttributes();

    $hasLazy = false;
    foreach ($attributes as $attribute) {
        if ($attribute->getName() === 'Livewire\Attributes\Lazy') {
            $hasLazy = true;
            break;
        }
    }

    expect($hasLazy)->toBeTrue('BuyerQuotePanel should have #[Lazy] attribute');
});

test('buyer quote panel has required public properties', function () {
    $reflection = new ReflectionClass(BuyerQuotePanel::class);
    $properties = collect($reflection->getProperties(\ReflectionProperty::IS_PUBLIC))
        ->map(fn($p) => $p->getName())
        ->toArray();

    expect($properties)->toContain('quotesLoaded');
    expect($properties)->toContain('quotes');
    expect($properties)->toContain('activeQuotesCount');
});

test('buyer quote panel has required methods', function () {
    $reflection = new ReflectionClass(BuyerQuotePanel::class);
    $methods = collect($reflection->getMethods(\ReflectionMethod::IS_PUBLIC))
        ->map(fn($m) => $m->getName())
        ->toArray();

    expect($methods)->toContain('mount');
    expect($methods)->toContain('loadQuotes');
    expect($methods)->toContain('onQuoteReceived');
    expect($methods)->toContain('getListeners');
    expect($methods)->toContain('render');
    expect($methods)->toContain('placeholder');
});

test('buyer quote panel render method returns correct view', function () {
    $component = new BuyerQuotePanel();
    $view = $component->render();

    expect($view->name())->toBe('livewire.quotes.buyer-quote-panel');
});

test('buyer quote panel view file exists', function () {
    $viewPath = resource_path('views/livewire/quotes/buyer-quote-panel.blade.php');

    expect(file_exists($viewPath))->toBeTrue('View file should exist at ' . $viewPath);
});

test('quote timer javascript file exists', function () {
    $jsPath = public_path('assets/js/buyer/quotes/quote-timers.js');

    expect(file_exists($jsPath))->toBeTrue('Timer JS file should exist');
});

test('quote modal javascript file exists', function () {
    $jsPath = public_path('assets/js/buyer/quotes/quote-modal.js');

    expect(file_exists($jsPath))->toBeTrue('Modal JS file should exist');
});

test('buyer quote panel follows naming convention', function () {
    // Naming pattern: Quotes/BuyerQuotePanel (matches Messaging/BuyerMessenger)
    $quotesClass = 'App\Livewire\Quotes\BuyerQuotePanel';
    $messagingClass = 'App\Livewire\Messaging\BuyerMessenger';

    expect(class_exists($quotesClass))->toBeTrue();
    expect(class_exists($messagingClass))->toBeTrue();

    // Both should follow same folder/naming pattern
    expect($quotesClass)->toContain('Livewire\Quotes\Buyer');
    expect($messagingClass)->toContain('Livewire\Messaging\Buyer');
});
