<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Subcategory;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            'Men' => ['Shirts', 'T-Shirts', 'Trousers', 'Jeans', 'Kurta', 'Sherwani'],
            'Women' => ['Tops', 'Kurtis', 'Sarees', 'Dresses', 'Abayas', 'Lawn'],
            'Kids' => ['Boys', 'Girls', 'Infant Wear'],
            'Accessories' => ['Belts', 'Caps', 'Socks', 'Scarves', 'Bags'],
            'Footwear' => ['Men Shoes', 'Women Shoes', 'Kids Shoes'],
        ];

        foreach ($data as $categoryName => $subcategories) {
            $category = Category::create(['name' => $categoryName]);

            foreach ($subcategories as $sub) {
                Subcategory::create([
                    'category_id' => $category->id,
                    'name' => $sub,
                ]);
            }
        }
    }
}
