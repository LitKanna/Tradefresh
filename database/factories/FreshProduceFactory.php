<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class FreshProduceFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Real Australian market produce data
     */
    private $produceData = [
        'vegetables' => [
            ['name' => 'Roma Tomatoes', 'price' => [3.50, 4.90], 'unit' => 'kg'],
            ['name' => 'Cherry Tomatoes', 'price' => [8.90, 12.50], 'unit' => 'punnet'],
            ['name' => 'Iceberg Lettuce', 'price' => [2.50, 3.90], 'unit' => 'each'],
            ['name' => 'Cos Lettuce', 'price' => [3.50, 4.90], 'unit' => 'each'],
            ['name' => 'Baby Spinach', 'price' => [12.00, 16.00], 'unit' => 'kg'],
            ['name' => 'Broccoli', 'price' => [4.90, 7.90], 'unit' => 'kg'],
            ['name' => 'Cauliflower', 'price' => [4.50, 6.90], 'unit' => 'each'],
            ['name' => 'Carrots', 'price' => [1.90, 2.90], 'unit' => 'kg'],
            ['name' => 'Sweet Potato', 'price' => [3.90, 5.90], 'unit' => 'kg'],
            ['name' => 'Red Capsicum', 'price' => [8.90, 12.90], 'unit' => 'kg'],
            ['name' => 'Green Capsicum', 'price' => [6.90, 9.90], 'unit' => 'kg'],
            ['name' => 'Mushrooms Button', 'price' => [10.90, 14.90], 'unit' => 'kg'],
            ['name' => 'Brown Onions', 'price' => [1.90, 2.90], 'unit' => 'kg'],
            ['name' => 'Red Onions', 'price' => [2.90, 4.50], 'unit' => 'kg'],
            ['name' => 'Spring Onions', 'price' => [2.50, 3.90], 'unit' => 'bunch'],
            ['name' => 'Garlic', 'price' => [18.90, 24.90], 'unit' => 'kg'],
            ['name' => 'Ginger', 'price' => [19.90, 29.90], 'unit' => 'kg'],
            ['name' => 'Celery', 'price' => [3.90, 5.90], 'unit' => 'bunch'],
            ['name' => 'Cucumber Lebanese', 'price' => [4.90, 7.90], 'unit' => 'kg'],
            ['name' => 'Zucchini', 'price' => [4.90, 7.90], 'unit' => 'kg'],
            ['name' => 'Eggplant', 'price' => [5.90, 8.90], 'unit' => 'kg'],
            ['name' => 'Pumpkin Butternut', 'price' => [2.90, 4.50], 'unit' => 'kg'],
            ['name' => 'Corn Cobs', 'price' => [1.50, 2.50], 'unit' => 'each'],
            ['name' => 'Green Beans', 'price' => [7.90, 12.90], 'unit' => 'kg'],
            ['name' => 'Snow Peas', 'price' => [14.90, 19.90], 'unit' => 'kg'],
            ['name' => 'Asparagus', 'price' => [2.90, 4.90], 'unit' => 'bunch']
        ],
        'fruits' => [
            ['name' => 'Pink Lady Apples', 'price' => [4.90, 6.90], 'unit' => 'kg'],
            ['name' => 'Granny Smith Apples', 'price' => [3.90, 5.90], 'unit' => 'kg'],
            ['name' => 'Royal Gala Apples', 'price' => [4.50, 6.50], 'unit' => 'kg'],
            ['name' => 'Cavendish Bananas', 'price' => [2.90, 4.50], 'unit' => 'kg'],
            ['name' => 'Lady Finger Bananas', 'price' => [4.90, 6.90], 'unit' => 'kg'],
            ['name' => 'Navel Oranges', 'price' => [2.90, 4.50], 'unit' => 'kg'],
            ['name' => 'Valencia Oranges', 'price' => [2.50, 3.90], 'unit' => 'kg'],
            ['name' => 'Mandarins Imperial', 'price' => [3.90, 5.90], 'unit' => 'kg'],
            ['name' => 'Lemons', 'price' => [3.90, 5.90], 'unit' => 'kg'],
            ['name' => 'Limes', 'price' => [19.90, 29.90], 'unit' => 'kg'],
            ['name' => 'Strawberries', 'price' => [3.90, 6.90], 'unit' => 'punnet'],
            ['name' => 'Blueberries', 'price' => [5.90, 9.90], 'unit' => 'punnet'],
            ['name' => 'Raspberries', 'price' => [6.90, 10.90], 'unit' => 'punnet'],
            ['name' => 'Red Grapes Seedless', 'price' => [7.90, 12.90], 'unit' => 'kg'],
            ['name' => 'Green Grapes Seedless', 'price' => [7.90, 12.90], 'unit' => 'kg'],
            ['name' => 'Watermelon', 'price' => [1.90, 2.90], 'unit' => 'kg'],
            ['name' => 'Rockmelon', 'price' => [2.90, 4.50], 'unit' => 'each'],
            ['name' => 'Honeydew Melon', 'price' => [3.90, 5.90], 'unit' => 'each'],
            ['name' => 'Pineapple', 'price' => [3.50, 5.50], 'unit' => 'each'],
            ['name' => 'Mango Kensington Pride', 'price' => [3.90, 5.90], 'unit' => 'each'],
            ['name' => 'Avocado Hass', 'price' => [2.50, 4.00], 'unit' => 'each'],
            ['name' => 'Kiwi Fruit', 'price' => [9.90, 14.90], 'unit' => 'kg'],
            ['name' => 'Pears Packham', 'price' => [3.90, 5.90], 'unit' => 'kg'],
            ['name' => 'Peaches Yellow', 'price' => [5.90, 8.90], 'unit' => 'kg'],
            ['name' => 'Nectarines', 'price' => [5.90, 8.90], 'unit' => 'kg'],
            ['name' => 'Plums', 'price' => [6.90, 9.90], 'unit' => 'kg']
        ],
        'herbs' => [
            ['name' => 'Basil Fresh', 'price' => [3.50, 4.90], 'unit' => 'bunch'],
            ['name' => 'Coriander Fresh', 'price' => [2.90, 3.90], 'unit' => 'bunch'],
            ['name' => 'Parsley Continental', 'price' => [2.50, 3.50], 'unit' => 'bunch'],
            ['name' => 'Parsley Curly', 'price' => [2.50, 3.50], 'unit' => 'bunch'],
            ['name' => 'Mint Fresh', 'price' => [2.90, 3.90], 'unit' => 'bunch'],
            ['name' => 'Oregano Fresh', 'price' => [3.90, 4.90], 'unit' => 'bunch'],
            ['name' => 'Thyme Fresh', 'price' => [3.90, 4.90], 'unit' => 'bunch'],
            ['name' => 'Rosemary Fresh', 'price' => [3.90, 4.90], 'unit' => 'bunch'],
            ['name' => 'Sage Fresh', 'price' => [3.90, 4.90], 'unit' => 'bunch'],
            ['name' => 'Dill Fresh', 'price' => [3.50, 4.50], 'unit' => 'bunch'],
            ['name' => 'Chives Fresh', 'price' => [3.50, 4.50], 'unit' => 'bunch'],
            ['name' => 'Bay Leaves Fresh', 'price' => [4.90, 5.90], 'unit' => 'bunch']
        ],
        'organic' => [
            ['name' => 'Organic Tomatoes', 'price' => [6.90, 9.90], 'unit' => 'kg'],
            ['name' => 'Organic Carrots', 'price' => [4.90, 6.90], 'unit' => 'kg'],
            ['name' => 'Organic Broccoli', 'price' => [8.90, 12.90], 'unit' => 'kg'],
            ['name' => 'Organic Spinach', 'price' => [16.90, 22.90], 'unit' => 'kg'],
            ['name' => 'Organic Bananas', 'price' => [5.90, 7.90], 'unit' => 'kg'],
            ['name' => 'Organic Apples', 'price' => [7.90, 10.90], 'unit' => 'kg'],
            ['name' => 'Organic Blueberries', 'price' => [9.90, 14.90], 'unit' => 'punnet'],
            ['name' => 'Organic Strawberries', 'price' => [7.90, 11.90], 'unit' => 'punnet']
        ]
    ];

    /**
     * Australian states for origin
     */
    private $australianStates = ['NSW', 'VIC', 'QLD', 'SA', 'WA', 'TAS', 'NT'];

    /**
     * Quality grades
     */
    private $qualityGrades = ['Premium', 'A', 'B', 'Standard'];

    /**
     * Define the model's default state
     */
    public function definition()
    {
        $category = $this->faker->randomElement(array_keys($this->produceData));
        $product = $this->faker->randomElement($this->produceData[$category]);
        
        return [
            'name' => $product['name'],
            'category_id' => function() use ($category) {
                return Category::where('slug', $category)->first()->id ?? 
                       Category::factory()->create(['slug' => $category])->id;
            },
            'base_price' => $this->faker->randomFloat(2, $product['price'][0], $product['price'][1]),
            'unit' => $product['unit'],
            'description' => $this->generateDescription($product['name']),
            'stock_quantity' => $this->faker->numberBetween(10, 1000),
            'quality_grade' => $this->faker->randomElement($this->qualityGrades),
            'origin' => $this->faker->randomElement($this->australianStates),
            'harvest_date' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'best_before' => $this->faker->dateTimeBetween('+3 days', '+14 days'),
            'storage_temp' => $this->faker->randomElement(['2-4°C', '4-8°C', '8-12°C', 'Room temp']),
            'min_order_quantity' => $this->faker->randomElement([1, 5, 10, 20]),
            'bulk_discount_percentage' => $this->faker->randomElement([0, 5, 10, 15]),
            'bulk_discount_threshold' => $this->faker->randomElement([50, 100, 200]),
            'is_seasonal' => $this->faker->boolean(30),
            'is_featured' => $this->faker->boolean(20),
            'nutritional_info' => $this->generateNutritionalInfo(),
            'handling_instructions' => $this->generateHandlingInstructions($category),
            'certifications' => $this->generateCertifications()
        ];
    }

    /**
     * Generate realistic product description
     */
    private function generateDescription($productName)
    {
        $descriptions = [
            'default' => "Fresh, high-quality {$productName} sourced directly from Australian farms.",
            'tomatoes' => "Juicy and flavorful tomatoes, perfect for salads, cooking, and sauces.",
            'lettuce' => "Crisp and fresh lettuce, ideal for salads and sandwiches.",
            'apples' => "Crisp and sweet apples, perfect for eating fresh or cooking.",
            'bananas' => "Perfectly ripened bananas, great source of potassium and energy.",
            'herbs' => "Aromatic fresh herbs to enhance any dish with authentic flavors."
        ];

        foreach ($descriptions as $key => $desc) {
            if ($key !== 'default' && stripos($productName, $key) !== false) {
                return $desc;
            }
        }

        return str_replace('{$productName}', $productName, $descriptions['default']);
    }

    /**
     * Generate nutritional information
     */
    private function generateNutritionalInfo()
    {
        return json_encode([
            'calories' => $this->faker->numberBetween(15, 150),
            'protein' => $this->faker->randomFloat(1, 0.5, 5),
            'carbohydrates' => $this->faker->randomFloat(1, 2, 30),
            'fat' => $this->faker->randomFloat(1, 0, 5),
            'fiber' => $this->faker->randomFloat(1, 1, 8),
            'sugar' => $this->faker->randomFloat(1, 1, 20),
            'sodium' => $this->faker->numberBetween(5, 200),
            'vitamins' => $this->faker->randomElements(['A', 'C', 'K', 'B6', 'E'], 2)
        ]);
    }

    /**
     * Generate handling instructions
     */
    private function generateHandlingInstructions($category)
    {
        $instructions = [
            'vegetables' => 'Store in cool, dry place. Wash before use. Keep refrigerated after cutting.',
            'fruits' => 'Handle with care to avoid bruising. Store at room temperature until ripe, then refrigerate.',
            'herbs' => 'Keep stems in water. Store in refrigerator. Use within 3-5 days for best flavor.',
            'organic' => 'Certified organic produce. No pesticides or chemicals used. Wash gently before consumption.'
        ];

        return $instructions[$category] ?? $instructions['vegetables'];
    }

    /**
     * Generate certifications
     */
    private function generateCertifications()
    {
        $possibleCerts = [
            'Australian Certified Organic',
            'HACCP Certified',
            'SQF Certified',
            'GlobalGAP',
            'Freshcare Certified',
            'Fair Trade',
            'Rainforest Alliance'
        ];

        return json_encode($this->faker->randomElements($possibleCerts, rand(0, 3)));
    }

    /**
     * State for vegetables
     */
    public function vegetable()
    {
        return $this->state(function (array $attributes) {
            $product = $this->faker->randomElement($this->produceData['vegetables']);
            return [
                'name' => $product['name'],
                'category_id' => Category::where('slug', 'vegetables')->first()->id,
                'base_price' => $this->faker->randomFloat(2, $product['price'][0], $product['price'][1]),
                'unit' => $product['unit']
            ];
        });
    }

    /**
     * State for fruits
     */
    public function fruit()
    {
        return $this->state(function (array $attributes) {
            $product = $this->faker->randomElement($this->produceData['fruits']);
            return [
                'name' => $product['name'],
                'category_id' => Category::where('slug', 'fruits')->first()->id,
                'base_price' => $this->faker->randomFloat(2, $product['price'][0], $product['price'][1]),
                'unit' => $product['unit']
            ];
        });
    }

    /**
     * State for premium products
     */
    public function premium()
    {
        return $this->state(function (array $attributes) {
            return [
                'quality_grade' => 'Premium',
                'base_price' => $attributes['base_price'] * 1.3, // 30% premium
                'is_featured' => true
            ];
        });
    }

    /**
     * State for bulk orders
     */
    public function bulk()
    {
        return $this->state(function (array $attributes) {
            return [
                'min_order_quantity' => 50,
                'bulk_discount_percentage' => 15,
                'bulk_discount_threshold' => 100,
                'stock_quantity' => $this->faker->numberBetween(500, 5000)
            ];
        });
    }
}