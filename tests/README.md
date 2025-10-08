# Test Directory Structure

## Organization

```
tests/
├── Feature/                   ← Feature/Integration tests
│   ├── Auth/                 ← Authentication tests
│   │   └── BuyerAuthenticationTest.php
│   ├── Buyer/                ← Buyer feature tests
│   │   ├── RFQSystemTest.php
│   │   └── QuoteSystemTest.php
│   ├── Vendor/               ← Vendor feature tests
│   │   └── VendorRegistrationTest.php
│   └── Admin/                ← Admin feature tests
│
├── Unit/                      ← Unit tests
│   ├── Buyer/                ← Buyer unit tests
│   ├── Vendor/               ← Vendor unit tests
│   ├── Admin/                ← Admin unit tests
│   ├── Services/             ← Service class tests
│   └── Models/               ← Model tests
│
├── Integration/              ← Integration tests
│   ├── OrderCardValidationTest.php
│   └── PaymentProcessingTest.php
│
├── TestCase.php              ← Base test class
└── CreatesApplication.php    ← Application factory trait
```

## Running Tests

### Run all tests
```bash
php artisan test
```

### Run specific test suites
```bash
# Feature tests only
php artisan test --testsuite=Feature

# Unit tests only
php artisan test --testsuite=Unit

# Buyer tests only
php artisan test tests/Feature/Buyer
php artisan test tests/Unit/Buyer

# Vendor tests only
php artisan test tests/Feature/Vendor
php artisan test tests/Unit/Vendor
```

### Run specific test file
```bash
php artisan test tests/Feature/Buyer/RFQSystemTest.php
```

### Run with coverage
```bash
php artisan test --coverage
```

## Test Naming Convention

- **Feature Tests**: `{Feature}Test.php`
  - Example: `RFQSystemTest.php`, `QuoteSystemTest.php`

- **Unit Tests**: `{Class}Test.php`
  - Example: `CartServiceTest.php`, `BuyerModelTest.php`

- **Integration Tests**: `{Integration}Test.php`
  - Example: `PaymentProcessingTest.php`

## Writing Tests

### Feature Test Example
```php
namespace Tests\Feature\Buyer;

use Tests\TestCase;
use App\Models\Buyer;

class QuoteSystemTest extends TestCase
{
    public function test_buyer_can_request_quote()
    {
        $buyer = Buyer::factory()->create();

        $response = $this->actingAs($buyer, 'buyer')
            ->post('/buyer/quotes/request', [...]);

        $response->assertStatus(200);
    }
}
```

### Unit Test Example
```php
namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\QuoteService;

class QuoteServiceTest extends TestCase
{
    public function test_calculate_quote_total()
    {
        $service = new QuoteService();
        $total = $service->calculateTotal([...]);

        $this->assertEquals(100.00, $total);
    }
}
```

## Test Database

Tests use a separate SQLite database by default:
- Database: `database/testing.sqlite`
- Automatically migrated before tests
- Rolled back after each test

## Factories & Seeders

Use factories for test data:
```php
$buyer = Buyer::factory()->create();
$vendor = Vendor::factory()->withProducts(5)->create();
$order = Order::factory()->pending()->create();
```

## Assertions

Common assertions for B2B marketplace:
- `assertAuthenticated('buyer')`
- `assertGuest('vendor')`
- `assertDatabaseHas('quotes', [...])`
- `assertSessionHas('cart')`
- `assertRedirect('/buyer/dashboard')`