<?php

namespace Database\Seeders;

use App\Models\Bundle;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DesapegoDemoSeeder extends Seeder
{
    public function run(): void
    {
        $hobbit = Product::create([
            'name' => 'O Hobbit',
            'slug' => Str::slug('O Hobbit'),
            'category' => 'Livros',
            'condition' => 'Excelente',
            'description' => 'Livro usado, bem conservado.',
            'pix_price' => 35,
            'marketplace_price' => 42,
            'status' => 'available',
        ]);

        $silmarillion = Product::create([
            'name' => 'O Silmarillion',
            'slug' => Str::slug('O Silmarillion'),
            'category' => 'Livros',
            'condition' => 'Excelente',
            'description' => 'Livro usado, bem conservado.',
            'pix_price' => 45,
            'marketplace_price' => 55,
            'status' => 'available',
        ]);

        $bundle = Bundle::create([
            'name' => 'Combo Tolkien',
            'slug' => 'combo-tolkien',
            'description' => 'Dois livros juntos por um valor menor.',
            'pix_price' => 70,
            'active' => true,
        ]);

        $bundle->products()->sync([$hobbit->id, $silmarillion->id]);
    }
}
