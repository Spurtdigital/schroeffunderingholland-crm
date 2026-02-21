<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class ListProducts extends Command
{
    protected $signature = 'test:list-products';
    protected $description = 'List all products';

    public function handle()
    {
        $products = Product::all();
        
        $this->info('=== All Products ===');
        $this->newLine();
        
        foreach ($products as $p) {
            $this->line("ID: {$p->id}");
            $this->line("  SKU: {$p->sku}");
            $this->line("  Name: {$p->name}");
            $this->line("  Price: €{$p->price}");
            $this->line("  Stock: {$p->stock_quantity}");
            $this->newLine();
        }
        
        return Command::SUCCESS;
    }
}
