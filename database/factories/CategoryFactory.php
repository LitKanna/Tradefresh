<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $categories = [
            'Fruits & Vegetables' => [
                'description' => 'Fresh fruits and vegetables from local and international suppliers',
                'subcategories' => ['Fruits', 'Vegetables', 'Herbs', 'Salads', 'Exotic'],
            ],
            'Meat & Poultry' => [
                'description' => 'Premium quality meat and poultry products',
                'subcategories' => ['Beef', 'Lamb', 'Pork', 'Chicken', 'Turkey', 'Game'],
            ],
            'Seafood' => [
                'description' => 'Fresh and frozen seafood from sustainable sources',
                'subcategories' => ['Fish', 'Shellfish', 'Crustaceans', 'Molluscs'],
            ],
            'Dairy & Eggs' => [
                'description' => 'Fresh dairy products and eggs',
                'subcategories' => ['Milk', 'Cheese', 'Yogurt', 'Butter', 'Eggs'],
            ],
            'Bakery & Pastry' => [
                'description' => 'Fresh baked goods and pastries',
                'subcategories' => ['Bread', 'Pastries', 'Cakes', 'Cookies'],
            ],
            'Flowers & Plants' => [
                'description' => 'Fresh cut flowers and potted plants',
                'subcategories' => ['Cut Flowers', 'Potted Plants', 'Arrangements'],
            ],
        ];
        
        $categoryName = $this->faker->randomElement(array_keys($categories));
        $category = $categories[$categoryName];
        
        return [
            'name' => $categoryName,
            'slug' => Str::slug($categoryName),
            'description' => $category['description'],
            'parent_id' => null,
            'image' => '/images/categories/' . Str::slug($categoryName) . '.jpg',
            'icon' => 'fa-' . Str::slug($categoryName),
            'is_active' => true,
            'display_order' => $this->faker->numberBetween(1, 10),
            'meta_title' => $categoryName . ' - Sydney Markets',
            'meta_description' => $category['description'],
            'meta_keywords' => implode(', ', array_merge([$categoryName], $category['subcategories'])),
            'product_count' => $this->faker->numberBetween(10, 200),
            'vendor_count' => $this->faker->numberBetween(5, 50),
        ];
    }
    
    public function withSubcategories(): static
    {
        return $this->state(function (array $attributes) {
            $subcategories = [
                'Fruits' => ['Apples', 'Bananas', 'Citrus', 'Stone Fruits', 'Berries'],
                'Vegetables' => ['Leafy Greens', 'Root Vegetables', 'Tomatoes', 'Onions', 'Peppers'],
                'Meat' => ['Beef', 'Lamb', 'Pork', 'Veal'],
                'Seafood' => ['Fish', 'Prawns', 'Oysters', 'Lobster'],
            ];
            
            $parentCategory = $this->faker->randomElement(array_keys($subcategories));
            
            return [
                'name' => $this->faker->randomElement($subcategories[$parentCategory]),
                'parent_id' => 1, // Will need to be set to actual parent ID
            ];
        });
    }
    
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'product_count' => 0,
            'vendor_count' => 0,
        ]);
    }
    
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'featured_image' => '/images/featured/' . Str::slug($attributes['name']) . '.jpg',
            'featured_until' => $this->faker->dateTimeBetween('now', '+1 month'),
        ]);
    }
}