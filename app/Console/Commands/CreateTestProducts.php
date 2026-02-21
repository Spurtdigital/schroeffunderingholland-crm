<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class CreateTestProducts extends Command
{
    protected $signature = 'test:create-products';
    protected $description = 'Create test products for development';

    public function handle()
    {
        $products = [
            [
                'name' => 'Schroefpaal standaard',
                'sku' => 'SP-001',
                'description' => 'Standaard schroefpaal voor normale grondsoorten',
                'purchase_price' => 85.00,
                'sales_price' => 125.50,
                'stock_quantity' => 50,
                'min_stock_level' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Schroefpaal heavy duty',
                'sku' => 'SP-002',
                'description' => 'Versterkte schroefpaal voor zware belasting',
                'purchase_price' => 135.00,
                'sales_price' => 189.95,
                'stock_quantity' => 25,
                'min_stock_level' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Ankerplaat 200x200',
                'sku' => 'AP-200',
                'description' => 'Ankerplaat 200x200mm gegalvaniseerd',
                'purchase_price' => 28.00,
                'sales_price' => 45.00,
                'stock_quantity' => 100,
                'min_stock_level' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'Betonpoer type A',
                'sku' => 'BP-A',
                'description' => 'Betonpoer verstelbaar type A',
                'purchase_price' => 58.00,
                'sales_price' => 89.50,
                'stock_quantity' => 30,
                'min_stock_level' => 10,
                'is_active' => true,
            ],
        ];

        // Delete existing products first
        Product::whereIn('sku', ['SP-001', 'SP-002', 'AP-200', 'BP-A'])->delete();

        foreach ($products as $data) {
            Product::create($data);
            $this->info("Created product: {$data['name']} - €{$data['sales_price']}");
        }

        $this->info('Successfully created ' . count($products) . ' test products!');

        return Command::SUCCESS;
    }
}
