<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(rand(2, 4), true);
        $name = ucwords($name);
        
        $units = ['kg', 'litre', 'each', 'dozen', 'bunch', 'punnet', 'bag'];
        $unit = $this->faker->randomElement($units);
        
        $price = $this->faker->randomFloat(2, 1.50, 85.00);
        $costPrice = $price * (0.65 + (rand(0, 20) / 100)); // Cost is 65-85% of selling price
        $comparePrice = $price * (1.1 + (rand(0, 30) / 100)); // Compare price is 10-40% higher
        
        return [
            'vendor_id' => Vendor::factory(),
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(6),
            'description' => $this->faker->paragraph(2),
            'sku' => strtoupper($this->faker->bothify('???-####')),
            'barcode' => $this->faker->ean13(),
            'unit' => $unit,
            'unit_size' => $this->getUnitSize($unit),
            'price' => $price,
            'compare_price' => $comparePrice,
            'cost' => $costPrice,
            'stock_quantity' => $this->faker->numberBetween(0, 1000),
            'min_order_quantity' => $this->getMinOrderQuantity($unit),
            'max_order_quantity' => $this->faker->numberBetween(100, 1000),
            'track_inventory' => $this->faker->boolean(90),
            'is_active' => $this->faker->boolean(85),
            'is_featured' => $this->faker->boolean(15),
            'is_organic' => $this->faker->boolean(25),
            'is_seasonal' => $this->faker->boolean(35),
            'origin_country' => $this->faker->randomElement(['Australia', 'New Zealand', 'USA', 'Italy', 'France']),
            'brand' => $this->faker->company(),
            'images' => json_encode([
                '/images/products/product-' . rand(1, 50) . '.jpg',
                '/images/products/product-' . rand(51, 100) . '.jpg'
            ]),
            'specifications' => json_encode([
                'grade' => $this->faker->randomElement(['A', 'AA', 'Premium', 'Export Quality']),
                'packaging' => $this->faker->randomElement(['Bulk', 'Retail Ready', 'Food Service']),
                'size' => $this->faker->randomElement(['Small', 'Medium', 'Large', 'Extra Large'])
            ]),
            'certifications' => json_encode($this->getCertifications()),
            'tags' => json_encode($this->getTags()),
            'nutritional_info' => json_encode([
                'energy_per_100g' => $this->faker->numberBetween(50, 400) . 'kJ',
                'protein_per_100g' => $this->faker->numberBetween(0, 30) . 'g',
                'fat_per_100g' => $this->faker->numberBetween(0, 25) . 'g',
                'carbs_per_100g' => $this->faker->numberBetween(0, 50) . 'g'
            ]),
            'harvest_date' => $this->faker->boolean(50) ? $this->faker->dateTimeBetween('-14 days', 'now') : null,
            'expiry_date' => $this->faker->boolean(70) ? $this->faker->dateTimeBetween('now', '+30 days') : null,
            'shelf_life_days' => $this->faker->numberBetween(1, 90),
            'weight' => $unit === 'kg' ? null : $this->faker->randomFloat(3, 0.1, 5.0),
            'dimensions' => $this->faker->boolean(60) ? 
                rand(10, 30) . 'x' . rand(10, 30) . 'x' . rand(5, 20) . 'cm' : null,
            'rating' => $this->faker->randomFloat(1, 3.0, 5.0),
            'review_count' => $this->faker->numberBetween(0, 200),
            'view_count' => $this->faker->numberBetween(0, 1000),
            'order_count' => $this->faker->numberBetween(0, 500),
            'metadata' => json_encode([
                'storage_temp' => $this->faker->randomElement(['0-2°C', '2-4°C', '2-8°C', 'Ambient']),
                'handling_instructions' => 'Handle with care, keep refrigerated',
                'supplier_notes' => $this->faker->sentence()
            ])
        ];
    }

    /**
     * Indicate that the product is organic.
     */
    public function organic(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_organic' => true,
            'certifications' => json_encode(['Organic Certified', 'ACO Certified']),
            'price' => $attributes['price'] * 1.3, // 30% premium for organic
        ]);
    }

    /**
     * Indicate that the product is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'rating' => $this->faker->randomFloat(1, 4.0, 5.0), // Featured products have higher ratings
        ]);
    }

    /**
     * Indicate that the product is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the product is seasonal.
     */
    public function seasonal(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_seasonal' => true,
            'harvest_date' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'shelf_life_days' => $this->faker->numberBetween(3, 21), // Seasonal products have shorter shelf life
        ]);
    }

    private function getUnitSize($unit): ?float
    {
        $sizes = [
            'kg' => null,
            'litre' => null,
            'each' => 1,
            'dozen' => 12,
            'bunch' => 1,
            'punnet' => 0.25,
            'bag' => 0.5
        ];
        
        return $sizes[$unit] ?? null;
    }

    private function getMinOrderQuantity($unit): int
    {
        $quantities = [
            'kg' => $this->faker->numberBetween(1, 5),
            'litre' => $this->faker->numberBetween(1, 4),
            'each' => $this->faker->numberBetween(1, 10),
            'dozen' => $this->faker->numberBetween(1, 3),
            'bunch' => $this->faker->numberBetween(2, 10),
            'punnet' => $this->faker->numberBetween(1, 8),
            'bag' => $this->faker->numberBetween(1, 5)
        ];
        
        return $quantities[$unit] ?? 1;
    }

    private function getCertifications(): array
    {
        $allCerts = [
            'HACCP', 'ISO 9001', 'Organic Certified', 'Free Range', 
            'MSC Certified', 'RSPCA Approved', 'Halal', 'Kosher'
        ];
        
        $count = $this->faker->numberBetween(0, 3);
        return $count > 0 ? $this->faker->randomElements($allCerts, $count) : [];
    }

    private function getTags(): array
    {
        $allTags = [
            'Premium', 'Fresh', 'Quality', 'Local', 'Imported', 
            'Seasonal', 'Popular', 'Chef\'s Choice', 'Best Seller',
            'New Arrival', 'Limited Edition', 'Bulk Available'
        ];
        
        $count = $this->faker->numberBetween(2, 6);
        return $this->faker->randomElements($allTags, $count);
    }
}