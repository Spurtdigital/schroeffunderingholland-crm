<?php

use App\Models\Product;

// Create test products
$product1 = Product::create([
    'name' => 'Schroefpaal standaard',
    'description' => 'Standaard schroefpaal voor woningbouw',
    'sku' => 'SP-STD-001',
    'price' => 125.50,
    'stock_quantity' => 50,
    'min_stock_level' => 10,
    'unit' => 'stuk',
    'is_active' => true,
]);
echo "✓ Product aangemaakt: {$product1->name} (€{$product1->price})\n";

$product2 = Product::create([
    'name' => 'Schroefpaal heavy duty',
    'description' => 'Versterkte schroefpaal voor zware constructies',
    'sku' => 'SP-HD-001',
    'price' => 189.95,
    'stock_quantity' => 25,
    'min_stock_level' => 5,
    'unit' => 'stuk',
    'is_active' => true,
]);
echo "✓ Product aangemaakt: {$product2->name} (€{$product2->price})\n";

$product3 = Product::create([
    'name' => 'Installatiekosten',
    'description' => 'Arbeid en materiaal voor installatie per paal',
    'sku' => 'SRV-INST-001',
    'price' => 45.00,
    'stock_quantity' => 0,
    'min_stock_level' => 0,
    'unit' => 'stuk',
    'is_active' => true,
]);
echo "✓ Product aangemaakt: {$product3->name} (€{$product3->price})\n";

echo "\nTotaal " . Product::count() . " producten in database\n";
