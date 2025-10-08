<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Vendor;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vendors = Vendor::all();
        $categories = Category::whereNotNull('parent_id')->get(); // Only subcategories

        if ($vendors->isEmpty() || $categories->isEmpty()) {
            $this->command->error('Please run VendorSeeder and CategorySeeder first.');
            return;
        }

        // Product templates by category
        $productTemplates = [
            // Fresh Produce
            'fruits' => [
                ['name' => 'Premium Royal Gala Apples', 'unit' => 'kg', 'price_min' => 3.50, 'price_max' => 5.20],
                ['name' => 'Organic Bananas', 'unit' => 'kg', 'price_min' => 4.80, 'price_max' => 6.50],
                ['name' => 'Fresh Strawberries', 'unit' => 'punnet', 'price_min' => 4.20, 'price_max' => 7.80],
                ['name' => 'Naval Oranges', 'unit' => 'kg', 'price_min' => 2.80, 'price_max' => 4.50],
                ['name' => 'Avocados - Hass', 'unit' => 'each', 'price_min' => 2.20, 'price_max' => 3.80],
                ['name' => 'Pineapples - Golden Sweet', 'unit' => 'each', 'price_min' => 6.50, 'price_max' => 9.20],
                ['name' => 'Mangoes - Kensington Pride', 'unit' => 'each', 'price_min' => 3.80, 'price_max' => 6.20],
                ['name' => 'Grapes - Red Globe', 'unit' => 'kg', 'price_min' => 5.50, 'price_max' => 8.90],
            ],
            'vegetables' => [
                ['name' => 'Baby Spinach Leaves', 'unit' => 'bag', 'price_min' => 3.20, 'price_max' => 4.80],
                ['name' => 'Premium Tomatoes', 'unit' => 'kg', 'price_min' => 4.50, 'price_max' => 7.20],
                ['name' => 'Lebanese Cucumbers', 'unit' => 'kg', 'price_min' => 3.80, 'price_max' => 5.50],
                ['name' => 'Red Capsicums', 'unit' => 'kg', 'price_min' => 6.20, 'price_max' => 9.80],
                ['name' => 'Sweet Potato', 'unit' => 'kg', 'price_min' => 2.90, 'price_max' => 4.20],
                ['name' => 'Broccoli Crowns', 'unit' => 'kg', 'price_min' => 4.80, 'price_max' => 7.50],
                ['name' => 'Carrots - Dutch', 'unit' => 'kg', 'price_min' => 2.20, 'price_max' => 3.80],
                ['name' => 'Mushrooms - Button', 'unit' => 'kg', 'price_min' => 7.50, 'price_max' => 12.20],
            ],
            'herbs-microgreens' => [
                ['name' => 'Fresh Basil', 'unit' => 'bunch', 'price_min' => 2.80, 'price_max' => 4.20],
                ['name' => 'Coriander', 'unit' => 'bunch', 'price_min' => 2.50, 'price_max' => 3.80],
                ['name' => 'Parsley - Continental', 'unit' => 'bunch', 'price_min' => 2.20, 'price_max' => 3.50],
                ['name' => 'Microgreens Mix', 'unit' => 'punnet', 'price_min' => 5.50, 'price_max' => 8.20],
                ['name' => 'Fresh Mint', 'unit' => 'bunch', 'price_min' => 2.80, 'price_max' => 4.00],
                ['name' => 'Rosemary', 'unit' => 'bunch', 'price_min' => 3.20, 'price_max' => 4.50],
            ],
            // Meat & Poultry
            'beef' => [
                ['name' => 'Premium Beef Scotch Fillet', 'unit' => 'kg', 'price_min' => 35.00, 'price_max' => 45.00],
                ['name' => 'Beef Eye Fillet', 'unit' => 'kg', 'price_min' => 48.00, 'price_max' => 65.00],
                ['name' => 'Beef Chuck Steak', 'unit' => 'kg', 'price_min' => 16.50, 'price_max' => 22.00],
                ['name' => 'Premium Mince Beef', 'unit' => 'kg', 'price_min' => 12.50, 'price_max' => 18.00],
                ['name' => 'Beef Brisket', 'unit' => 'kg', 'price_min' => 14.00, 'price_max' => 19.50],
                ['name' => 'Wagyu Beef Ribeye', 'unit' => 'kg', 'price_min' => 85.00, 'price_max' => 120.00],
            ],
            'chicken' => [
                ['name' => 'Free Range Chicken Breast', 'unit' => 'kg', 'price_min' => 12.50, 'price_max' => 16.80],
                ['name' => 'Whole Chicken - Free Range', 'unit' => 'kg', 'price_min' => 8.50, 'price_max' => 12.20],
                ['name' => 'Chicken Thigh Fillets', 'unit' => 'kg', 'price_min' => 9.80, 'price_max' => 13.50],
                ['name' => 'Chicken Wings', 'unit' => 'kg', 'price_min' => 6.50, 'price_max' => 9.80],
                ['name' => 'Organic Chicken Drumsticks', 'unit' => 'kg', 'price_min' => 8.20, 'price_max' => 11.50],
            ],
            'lamb' => [
                ['name' => 'Lamb Leg - Boneless', 'unit' => 'kg', 'price_min' => 28.00, 'price_max' => 35.00],
                ['name' => 'Lamb Cutlets - French Trim', 'unit' => 'kg', 'price_min' => 42.00, 'price_max' => 55.00],
                ['name' => 'Lamb Shoulder', 'unit' => 'kg', 'price_min' => 18.50, 'price_max' => 24.00],
                ['name' => 'Lamb Mince', 'unit' => 'kg', 'price_min' => 14.50, 'price_max' => 19.00],
            ],
            // Seafood
            'fresh-fish' => [
                ['name' => 'Atlantic Salmon Fillets', 'unit' => 'kg', 'price_min' => 28.00, 'price_max' => 38.00],
                ['name' => 'Barramundi Fillets', 'unit' => 'kg', 'price_min' => 32.00, 'price_max' => 42.00],
                ['name' => 'Snapper - Whole', 'unit' => 'kg', 'price_min' => 24.00, 'price_max' => 32.00],
                ['name' => 'Tuna Steaks - Yellowfin', 'unit' => 'kg', 'price_min' => 45.00, 'price_max' => 60.00],
                ['name' => 'Flathead Fillets', 'unit' => 'kg', 'price_min' => 22.00, 'price_max' => 28.00],
            ],
            'shellfish' => [
                ['name' => 'King Prawns - Large', 'unit' => 'kg', 'price_min' => 45.00, 'price_max' => 65.00],
                ['name' => 'Blue Swimmer Crabs', 'unit' => 'kg', 'price_min' => 35.00, 'price_max' => 48.00],
                ['name' => 'Sydney Rock Oysters', 'unit' => 'dozen', 'price_min' => 18.00, 'price_max' => 28.00],
                ['name' => 'Lobster Tails', 'unit' => 'kg', 'price_min' => 85.00, 'price_max' => 120.00],
                ['name' => 'Scallops - Diver Caught', 'unit' => 'kg', 'price_min' => 55.00, 'price_max' => 75.00],
            ],
            // Dairy & Eggs
            'milk-cream' => [
                ['name' => 'Full Cream Milk', 'unit' => 'litre', 'price_min' => 1.40, 'price_max' => 2.20],
                ['name' => 'Heavy Cream', 'unit' => 'litre', 'price_min' => 4.50, 'price_max' => 6.80],
                ['name' => 'Buttermilk', 'unit' => 'litre', 'price_min' => 2.80, 'price_max' => 4.20],
                ['name' => 'Organic Milk', 'unit' => 'litre', 'price_min' => 2.50, 'price_max' => 3.80],
            ],
            'cheese' => [
                ['name' => 'Aged Cheddar Cheese', 'unit' => 'kg', 'price_min' => 18.00, 'price_max' => 25.00],
                ['name' => 'Mozzarella - Fresh', 'unit' => 'kg', 'price_min' => 16.00, 'price_max' => 22.00],
                ['name' => 'Parmesan - Aged 24 months', 'unit' => 'kg', 'price_min' => 45.00, 'price_max' => 65.00],
                ['name' => 'Brie Cheese', 'unit' => 'kg', 'price_min' => 22.00, 'price_max' => 32.00],
                ['name' => 'Blue Cheese', 'unit' => 'kg', 'price_min' => 25.00, 'price_max' => 38.00],
            ],
            'eggs' => [
                ['name' => 'Free Range Eggs - Large', 'unit' => 'dozen', 'price_min' => 4.50, 'price_max' => 6.80],
                ['name' => 'Organic Eggs', 'unit' => 'dozen', 'price_min' => 6.50, 'price_max' => 9.20],
                ['name' => 'Duck Eggs', 'unit' => 'dozen', 'price_min' => 8.50, 'price_max' => 12.00],
                ['name' => 'Quail Eggs', 'unit' => 'dozen', 'price_min' => 12.00, 'price_max' => 16.00],
            ],
        ];

        $createdProducts = 0;
        
        foreach ($productTemplates as $categorySlug => $products) {
            $category = $categories->firstWhere('slug', $categorySlug);
            if (!$category) continue;

            foreach ($products as $productData) {
                // Randomly assign to vendors (each product to 2-4 vendors)
                $selectedVendors = $vendors->random(rand(2, 4));
                
                foreach ($selectedVendors as $vendor) {
                    $price = $this->randomPrice($productData['price_min'], $productData['price_max']);
                    $costPrice = $price * (0.65 + (rand(0, 20) / 100)); // Cost is 65-85% of selling price
                    $comparePrice = $price * (1.1 + (rand(0, 30) / 100)); // Compare price is 10-40% higher
                    
                    $slug = Str::slug($productData['name'] . '-' . $vendor->business_name);
                    
                    Product::create([
                        'vendor_id' => $vendor->id,
                        'category_id' => $category->id,
                        'name' => $productData['name'],
                        'slug' => $slug,
                        'description' => $this->generateProductDescription($productData['name'], $vendor->business_name),
                        'sku' => strtoupper(Str::random(3) . '-' . rand(1000, 9999)),
                        'barcode' => rand(1000000000000, 9999999999999),
                        'unit' => $productData['unit'],
                        'unit_size' => $this->getUnitSize($productData['unit']),
                        'price' => $price,
                        'compare_price' => $comparePrice,
                        'cost' => $costPrice,
                        'stock_quantity' => rand(10, 500),
                        'min_order_quantity' => $this->getMinOrderQuantity($productData['unit']),
                        'max_order_quantity' => rand(100, 1000),
                        'track_inventory' => true,
                        'is_active' => rand(0, 10) > 1, // 90% active
                        'is_featured' => rand(0, 10) > 8, // 20% featured
                        'is_organic' => $this->isOrganic($productData['name']),
                        'is_seasonal' => $this->isSeasonal($categorySlug),
                        'origin_country' => $this->getOriginCountry($categorySlug),
                        'brand' => $this->getBrand($vendor->business_name),
                        'images' => json_encode([
                            '/images/products/' . $slug . '-1.jpg',
                            '/images/products/' . $slug . '-2.jpg'
                        ]),
                        'specifications' => json_encode($this->getSpecifications($categorySlug, $productData)),
                        'certifications' => json_encode($this->getCertifications($categorySlug)),
                        'tags' => json_encode($this->getTags($productData['name'], $categorySlug)),
                        'nutritional_info' => json_encode($this->getNutritionalInfo($categorySlug)),
                        'harvest_date' => $this->getHarvestDate($categorySlug),
                        'expiry_date' => $this->getExpiryDate($categorySlug),
                        'shelf_life_days' => $this->getShelfLife($categorySlug),
                        'weight' => $this->getWeight($productData['unit']),
                        'dimensions' => $this->getDimensions($productData['unit']),
                        'rating' => round(3.5 + (rand(0, 15) / 10), 1), // 3.5 to 5.0 rating
                        'review_count' => rand(0, 150),
                        'view_count' => rand(10, 1000),
                        'order_count' => rand(0, 200),
                        'metadata' => json_encode([
                            'storage_temp' => $this->getStorageTemp($categorySlug),
                            'handling_instructions' => $this->getHandlingInstructions($categorySlug),
                            'allergen_info' => $this->getAllergenInfo($categorySlug)
                        ])
                    ]);
                    
                    $createdProducts++;
                }
            }
        }

        $this->command->info("Products seeded successfully. Created {$createdProducts} products.");
    }

    private function randomPrice($min, $max): float
    {
        return round($min + (rand(0, 100) / 100) * ($max - $min), 2);
    }

    private function generateProductDescription($name, $vendorName): string
    {
        $descriptions = [
            "Premium quality {$name} sourced by {$vendorName}. Fresh daily delivery available.",
            "High-grade {$name} perfect for professional kitchens. Consistent quality guaranteed.",
            "Fresh {$name} from {$vendorName} - your trusted supplier for over 10 years.",
            "Restaurant-quality {$name} with full traceability and quality assurance."
        ];
        
        return $descriptions[array_rand($descriptions)];
    }

    private function getUnitSize($unit): ?float
    {
        $sizes = [
            'kg' => null,
            'litre' => null,
            'each' => 1,
            'bunch' => 1,
            'punnet' => 250,
            'bag' => 500,
            'dozen' => 12
        ];
        
        return $sizes[$unit] ?? null;
    }

    private function getMinOrderQuantity($unit): int
    {
        $quantities = [
            'kg' => rand(1, 5),
            'litre' => rand(1, 4),
            'each' => rand(1, 6),
            'bunch' => rand(2, 10),
            'punnet' => rand(1, 8),
            'bag' => rand(1, 5),
            'dozen' => rand(1, 3)
        ];
        
        return $quantities[$unit] ?? 1;
    }

    private function isOrganic($name): bool
    {
        return str_contains(strtolower($name), 'organic') || rand(0, 10) > 8;
    }

    private function isSeasonal($categorySlug): bool
    {
        $seasonalCategories = ['fruits', 'vegetables', 'herbs-microgreens'];
        return in_array($categorySlug, $seasonalCategories) && rand(0, 10) > 6;
    }

    private function getOriginCountry($categorySlug): string
    {
        $countries = [
            'fruits' => ['Australia', 'New Zealand', 'Chile', 'USA'],
            'vegetables' => ['Australia', 'New Zealand'],
            'beef' => ['Australia'],
            'lamb' => ['Australia', 'New Zealand'],
            'chicken' => ['Australia'],
            'fresh-fish' => ['Australia', 'New Zealand', 'Tasmania'],
            'shellfish' => ['Australia', 'Tasmania'],
            'milk-cream' => ['Australia'],
            'cheese' => ['Australia', 'France', 'Italy', 'Switzerland'],
            'eggs' => ['Australia']
        ];
        
        $options = $countries[$categorySlug] ?? ['Australia'];
        return $options[array_rand($options)];
    }

    private function getBrand($vendorName): string
    {
        return explode(' ', $vendorName)[0] . ' Premium';
    }

    private function getSpecifications($categorySlug, $productData): array
    {
        $specs = [
            'grade' => ['A', 'AA', 'Premium', 'Export Quality'][rand(0, 3)],
            'packaging' => ['Bulk', 'Retail Ready', 'Food Service'][rand(0, 2)],
            'size' => $this->getProductSize($categorySlug)
        ];
        
        if (in_array($categorySlug, ['beef', 'lamb', 'chicken'])) {
            $specs['cut_type'] = ['Primal', 'Secondary', 'Fabricated'][rand(0, 2)];
            $specs['aging'] = rand(7, 28) . ' days';
        }
        
        return $specs;
    }

    private function getProductSize($categorySlug): string
    {
        $sizes = [
            'fruits' => ['Small', 'Medium', 'Large', 'Extra Large'][rand(0, 3)],
            'vegetables' => ['Baby', 'Medium', 'Large'][rand(0, 2)],
            'beef' => ['Portion Cut', 'Whole Muscle'][rand(0, 1)],
            'fresh-fish' => ['Portion', 'Whole', 'Fillet'][rand(0, 2)]
        ];
        
        return $sizes[$categorySlug] ?? 'Standard';
    }

    private function getCertifications($categorySlug): array
    {
        $allCerts = ['HACCP', 'ISO 9001', 'Organic Certified', 'Free Range', 'MSC Certified', 'RSPCA Approved'];
        
        $categorySpecific = [
            'organic-produce' => ['Organic Certified', 'Biodynamic'],
            'beef' => ['HACCP', 'MSA Graded', 'Grass Fed'],
            'chicken' => ['Free Range', 'RSPCA Approved', 'Antibiotic Free'],
            'fresh-fish' => ['MSC Certified', 'Ocean Wise'],
            'milk-cream' => ['Organic Certified', 'A2 Milk']
        ];
        
        $certs = $categorySpecific[$categorySlug] ?? array_slice($allCerts, 0, rand(1, 3));
        return array_slice($certs, 0, rand(1, min(3, count($certs))));
    }

    private function getTags($name, $categorySlug): array
    {
        $commonTags = ['Premium', 'Fresh', 'Quality'];
        $categoryTags = [
            'fruits' => ['Seasonal', 'Sweet', 'Juicy', 'Crisp'],
            'vegetables' => ['Seasonal', 'Crisp', 'Garden Fresh', 'Local'],
            'beef' => ['Grass Fed', 'Grain Fed', 'Aged', 'Tender'],
            'chicken' => ['Free Range', 'Tender', 'Lean', 'Fresh'],
            'fresh-fish' => ['Ocean Fresh', 'Wild Caught', 'Sustainable', 'Filleted']
        ];
        
        $tags = array_merge($commonTags, $categoryTags[$categorySlug] ?? []);
        return array_slice($tags, 0, rand(3, min(5, count($tags))));
    }

    private function getNutritionalInfo($categorySlug): ?array
    {
        if (!in_array($categorySlug, ['fruits', 'vegetables', 'beef', 'chicken', 'fresh-fish', 'milk-cream', 'cheese'])) {
            return null;
        }
        
        return [
            'energy_per_100g' => rand(50, 300) . 'kJ',
            'protein_per_100g' => rand(1, 25) . 'g',
            'fat_per_100g' => rand(0, 20) . 'g',
            'carbs_per_100g' => rand(0, 30) . 'g',
            'sodium_per_100g' => rand(10, 500) . 'mg'
        ];
    }

    private function getHarvestDate($categorySlug): ?string
    {
        if (in_array($categorySlug, ['fruits', 'vegetables', 'herbs-microgreens'])) {
            return now()->subDays(rand(0, 7))->format('Y-m-d');
        }
        return null;
    }

    private function getExpiryDate($categorySlug): ?string
    {
        $shelfLife = $this->getShelfLife($categorySlug);
        if ($shelfLife) {
            return now()->addDays($shelfLife)->format('Y-m-d');
        }
        return null;
    }

    private function getShelfLife($categorySlug): ?int
    {
        $shelfLives = [
            'fruits' => rand(3, 14),
            'vegetables' => rand(2, 10),
            'herbs-microgreens' => rand(2, 7),
            'beef' => rand(3, 7),
            'chicken' => rand(2, 5),
            'fresh-fish' => rand(1, 3),
            'shellfish' => rand(1, 2),
            'milk-cream' => rand(5, 14),
            'cheese' => rand(14, 90),
            'eggs' => 35
        ];
        
        return $shelfLives[$categorySlug] ?? null;
    }

    private function getWeight($unit): ?float
    {
        if ($unit === 'kg') return null; // Weight is the unit itself
        
        $weights = [
            'litre' => 1.0,
            'each' => round(rand(50, 2000) / 1000, 3),
            'bunch' => round(rand(100, 500) / 1000, 3),
            'punnet' => 0.25,
            'bag' => 0.5,
            'dozen' => round(rand(600, 1200) / 1000, 3)
        ];
        
        return $weights[$unit] ?? null;
    }

    private function getDimensions($unit): ?string
    {
        if (in_array($unit, ['kg', 'litre'])) return null;
        
        return rand(10, 30) . 'x' . rand(10, 30) . 'x' . rand(5, 20) . 'cm';
    }

    private function getStorageTemp($categorySlug): string
    {
        $temps = [
            'fruits' => '2-8°C',
            'vegetables' => '0-4°C',
            'herbs-microgreens' => '2-4°C',
            'beef' => '0-2°C',
            'chicken' => '0-2°C',
            'fresh-fish' => '0-2°C',
            'shellfish' => '0-2°C',
            'milk-cream' => '2-4°C',
            'cheese' => '2-8°C',
            'eggs' => '2-8°C'
        ];
        
        return $temps[$categorySlug] ?? 'Ambient';
    }

    private function getHandlingInstructions($categorySlug): string
    {
        $instructions = [
            'fruits' => 'Handle with care, store in cool dry place',
            'vegetables' => 'Keep refrigerated, handle gently',
            'beef' => 'Keep chilled, use within use-by date',
            'fresh-fish' => 'Keep on ice, use within 2 days',
            'milk-cream' => 'Keep refrigerated, shake before use'
        ];
        
        return $instructions[$categorySlug] ?? 'Store in cool dry place';
    }

    private function getAllergenInfo($categorySlug): array
    {
        $allergens = [
            'milk-cream' => ['Contains: Milk'],
            'cheese' => ['Contains: Milk'],
            'eggs' => ['Contains: Eggs'],
            'fresh-fish' => ['Contains: Fish'],
            'shellfish' => ['Contains: Shellfish']
        ];
        
        return $allergens[$categorySlug] ?? ['No known allergens'];
    }
}