<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ImportLivrosCsvSeeder extends Seeder
{
    public function run(): void
    {
        $handle = fopen(storage_path('app/import-livros.csv'), 'rb');

        if ($handle === false) {
            throw new \RuntimeException('Não foi possível abrir o CSV.');
        }

        $headers = array_map(static fn (string $header): string => trim($header), fgetcsv($handle, 0, ';') ?: []);

        while (($values = fgetcsv($handle, 0, ';')) !== false) {
            if (count($values) !== count($headers)) {
                continue;
            }

            $row = array_combine($headers, $values);
            $name = trim((string) ($row['Titulo'] ?? ''));
            $individualPrice = $this->price($row['Preco_individual_sugerido_R$'] ?? '');
            $comboPrice = $this->price($row['Preco_combo_sugerido_R$'] ?? '');
            $price = $individualPrice ?? $comboPrice;

            if ($name === '' || $price === null || Product::where('name', $name)->exists()) {
                continue;
            }

            Product::create([
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'category' => 'Livros',
                'condition' => 'Usado — bom estado',
                'description' => trim((string) ($row['Descricao_para_anuncio'] ?? '')),
                'pix_price' => $price,
                'marketplace_price' => $price,
                'status' => 'available',
                'sort_order' => 0,
            ]);
        }

        fclose($handle);
    }

    private function price(string $value): ?float
    {
        $value = trim($value);

        return $value === '' ? null : (float) str_replace(',', '.', $value);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
