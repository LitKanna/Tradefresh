<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Main categories
            [
                'name' => 'Fresh Produce',
                'slug' => 'fresh-produce',
                'description' => 'Fresh fruits and vegetables from local farms and importers',
                'icon' => 'fruit-icon',
                'sort_order' => 1,
                'is_active' => true,
                'children' => [
                    ['name' => 'Fruits', 'slug' => 'fruits', 'description' => 'Fresh fruits - local and imported'],
                    ['name' => 'Vegetables', 'slug' => 'vegetables', 'description' => 'Fresh vegetables and salads'],
                    ['name' => 'Herbs & Microgreens', 'slug' => 'herbs-microgreens', 'description' => 'Fresh herbs and microgreens'],
                    ['name' => 'Organic Produce', 'slug' => 'organic-produce', 'description' => 'Certified organic fruits and vegetables'],
                    ['name' => 'Exotic & Tropical', 'slug' => 'exotic-tropical', 'description' => 'Exotic and tropical fruits and vegetables'],
                ]
            ],
            [
                'name' => 'Meat & Poultry',
                'slug' => 'meat-poultry',
                'description' => 'Quality meat, poultry, and game products',
                'icon' => 'meat-icon',
                'sort_order' => 2,
                'is_active' => true,
                'children' => [
                    ['name' => 'Beef', 'slug' => 'beef', 'description' => 'Premium beef cuts and products'],
                    ['name' => 'Lamb', 'slug' => 'lamb', 'description' => 'Fresh lamb and mutton'],
                    ['name' => 'Pork', 'slug' => 'pork', 'description' => 'Pork products and cuts'],
                    ['name' => 'Chicken', 'slug' => 'chicken', 'description' => 'Fresh chicken and poultry'],
                    ['name' => 'Game Meat', 'slug' => 'game-meat', 'description' => 'Venison, rabbit, and other game'],
                    ['name' => 'Processed Meat', 'slug' => 'processed-meat', 'description' => 'Sausages, bacon, and deli meats'],
                ]
            ],
            [
                'name' => 'Seafood',
                'slug' => 'seafood',
                'description' => 'Fresh and frozen seafood products',
                'icon' => 'fish-icon',
                'sort_order' => 3,
                'is_active' => true,
                'children' => [
                    ['name' => 'Fresh Fish', 'slug' => 'fresh-fish', 'description' => 'Daily fresh fish varieties'],
                    ['name' => 'Shellfish', 'slug' => 'shellfish', 'description' => 'Prawns, lobsters, crabs, and oysters'],
                    ['name' => 'Frozen Seafood', 'slug' => 'frozen-seafood', 'description' => 'Frozen fish and seafood products'],
                    ['name' => 'Smoked & Cured', 'slug' => 'smoked-cured', 'description' => 'Smoked salmon and cured seafood'],
                ]
            ],
            [
                'name' => 'Dairy & Eggs',
                'slug' => 'dairy-eggs',
                'description' => 'Dairy products, eggs, and alternatives',
                'icon' => 'dairy-icon',
                'sort_order' => 4,
                'is_active' => true,
                'children' => [
                    ['name' => 'Milk & Cream', 'slug' => 'milk-cream', 'description' => 'Fresh milk, cream, and alternatives'],
                    ['name' => 'Cheese', 'slug' => 'cheese', 'description' => 'Local and imported cheeses'],
                    ['name' => 'Yogurt & Desserts', 'slug' => 'yogurt-desserts', 'description' => 'Yogurt and dairy desserts'],
                    ['name' => 'Eggs', 'slug' => 'eggs', 'description' => 'Free-range, cage-free, and specialty eggs'],
                    ['name' => 'Butter & Margarine', 'slug' => 'butter-margarine', 'description' => 'Butter and spreads'],
                ]
            ],
            [
                'name' => 'Bakery & Pastry',
                'slug' => 'bakery-pastry',
                'description' => 'Fresh baked goods and pastries',
                'icon' => 'bread-icon',
                'sort_order' => 5,
                'is_active' => true,
                'children' => [
                    ['name' => 'Bread', 'slug' => 'bread', 'description' => 'Artisan and commercial breads'],
                    ['name' => 'Pastries', 'slug' => 'pastries', 'description' => 'Croissants, Danish, and pastries'],
                    ['name' => 'Cakes & Desserts', 'slug' => 'cakes-desserts', 'description' => 'Cakes and sweet treats'],
                    ['name' => 'Pizza Bases', 'slug' => 'pizza-bases', 'description' => 'Pizza dough and bases'],
                ]
            ],
            [
                'name' => 'Dry Goods & Pantry',
                'slug' => 'dry-goods-pantry',
                'description' => 'Pantry staples and dry goods',
                'icon' => 'pantry-icon',
                'sort_order' => 6,
                'is_active' => true,
                'children' => [
                    ['name' => 'Rice & Grains', 'slug' => 'rice-grains', 'description' => 'Rice, quinoa, and grains'],
                    ['name' => 'Pasta', 'slug' => 'pasta', 'description' => 'Fresh and dried pasta'],
                    ['name' => 'Flour & Baking', 'slug' => 'flour-baking', 'description' => 'Flour and baking ingredients'],
                    ['name' => 'Oils & Vinegars', 'slug' => 'oils-vinegars', 'description' => 'Cooking oils and vinegars'],
                    ['name' => 'Sauces & Condiments', 'slug' => 'sauces-condiments', 'description' => 'Sauces and condiments'],
                    ['name' => 'Spices & Seasonings', 'slug' => 'spices-seasonings', 'description' => 'Herbs, spices, and seasonings'],
                ]
            ],
            [
                'name' => 'Beverages',
                'slug' => 'beverages',
                'description' => 'Non-alcoholic beverages and drinks',
                'icon' => 'beverage-icon',
                'sort_order' => 7,
                'is_active' => true,
                'children' => [
                    ['name' => 'Juices', 'slug' => 'juices', 'description' => 'Fresh and packaged juices'],
                    ['name' => 'Soft Drinks', 'slug' => 'soft-drinks', 'description' => 'Carbonated beverages'],
                    ['name' => 'Coffee & Tea', 'slug' => 'coffee-tea', 'description' => 'Coffee beans and tea leaves'],
                    ['name' => 'Water', 'slug' => 'water', 'description' => 'Bottled and sparkling water'],
                ]
            ],
            [
                'name' => 'Frozen Foods',
                'slug' => 'frozen-foods',
                'description' => 'Frozen products and ready meals',
                'icon' => 'frozen-icon',
                'sort_order' => 8,
                'is_active' => true,
                'children' => [
                    ['name' => 'Frozen Vegetables', 'slug' => 'frozen-vegetables', 'description' => 'Frozen vegetable products'],
                    ['name' => 'Frozen Fruits', 'slug' => 'frozen-fruits', 'description' => 'Frozen fruit products'],
                    ['name' => 'Ice Cream & Desserts', 'slug' => 'ice-cream-desserts', 'description' => 'Frozen desserts'],
                    ['name' => 'Ready Meals', 'slug' => 'ready-meals', 'description' => 'Frozen ready-to-heat meals'],
                ]
            ],
            [
                'name' => 'Specialty & Gourmet',
                'slug' => 'specialty-gourmet',
                'description' => 'Specialty and gourmet products',
                'icon' => 'gourmet-icon',
                'sort_order' => 9,
                'is_active' => true,
                'children' => [
                    ['name' => 'Truffles & Caviar', 'slug' => 'truffles-caviar', 'description' => 'Luxury ingredients'],
                    ['name' => 'Organic Products', 'slug' => 'organic-products', 'description' => 'Certified organic items'],
                    ['name' => 'Gluten Free', 'slug' => 'gluten-free', 'description' => 'Gluten-free products'],
                    ['name' => 'Vegan Products', 'slug' => 'vegan-products', 'description' => 'Plant-based alternatives'],
                ]
            ],
            [
                'name' => 'Packaging & Supplies',
                'slug' => 'packaging-supplies',
                'description' => 'Food service packaging and supplies',
                'icon' => 'package-icon',
                'sort_order' => 10,
                'is_active' => true,
                'children' => [
                    ['name' => 'Take-away Containers', 'slug' => 'takeaway-containers', 'description' => 'Food packaging containers'],
                    ['name' => 'Disposable Cutlery', 'slug' => 'disposable-cutlery', 'description' => 'Disposable utensils'],
                    ['name' => 'Kitchen Supplies', 'slug' => 'kitchen-supplies', 'description' => 'Kitchen consumables'],
                    ['name' => 'Cleaning Products', 'slug' => 'cleaning-products', 'description' => 'Cleaning and hygiene products'],
                ]
            ],
        ];

        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);
            
            // Create parent category
            $parentCategory = Category::updateOrCreate(
                ['slug' => $categoryData['slug']],
                array_merge($categoryData, [
                    'metadata' => json_encode([
                        'featured' => true,
                        'banner_image' => '/images/categories/' . $categoryData['slug'] . '.jpg'
                    ]),
                    'seo' => json_encode([
                        'title' => $categoryData['name'] . ' - Sydney Markets B2B',
                        'description' => $categoryData['description'],
                        'keywords' => str_replace('-', ', ', $categoryData['slug'])
                    ])
                ])
            );

            // Create child categories
            foreach ($children as $index => $child) {
                Category::updateOrCreate(
                    ['slug' => $child['slug']],
                    [
                        'name' => $child['name'],
                        'slug' => $child['slug'],
                        'description' => $child['description'],
                        'parent_id' => $parentCategory->id,
                        'sort_order' => $index + 1,
                        'is_active' => true,
                        'metadata' => json_encode([
                            'featured' => false
                        ]),
                        'seo' => json_encode([
                            'title' => $child['name'] . ' - ' . $parentCategory->name,
                            'description' => $child['description']
                        ])
                    ]
                );
            }
        }

        $this->command->info('Categories seeded successfully.');
    }
}