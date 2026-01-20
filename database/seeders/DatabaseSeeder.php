<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin Minimarket',
            'email' => 'admin@minimarket.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create kasir users
        $kasir1 = User::create([
            'name' => 'Kasir 1',
            'email' => 'kasir1@minimarket.com',
            'password' => bcrypt('password'),
            'role' => 'kasir',
            'email_verified_at' => now(),
        ]);

        $kasir2 = User::create([
            'name' => 'Kasir 2',
            'email' => 'kasir2@minimarket.com',
            'password' => bcrypt('password'),
            'role' => 'kasir',
            'email_verified_at' => now(),
        ]);

        // Create categories
        $categories = [
            ['name' => 'Makanan & Minuman', 'description' => 'Produk makanan dan minuman'],
            ['name' => 'Elektronik', 'description' => 'Peralatan elektronik'],
            ['name' => 'Peralatan Rumah Tangga', 'description' => 'Keperluan rumah tangga'],
            ['name' => 'Kesehatan & Kecantikan', 'description' => 'Produk kesehatan dan kecantikan'],
            ['name' => 'Pakaian & Aksesoris', 'description' => 'Pakaian dan aksesoris'],
        ];

        foreach ($categories as $categoryData) {
            Category::create($categoryData);
        }

        // Create suppliers
        Supplier::factory(10)->create();

        // Create products with specific categories (Wrapped in ProductGroup)
        Category::all()->each(function ($category) {
            Product::factory(5)->make(['category_id' => $category->id])->each(function($product) use ($category) {
                 $group = ProductGroup::create([
                    'name' => $product->name,
                    'category_id' => $category->id,
                    'has_variants' => false
                 ]);
                 $product->product_group_id = $group->id;
                 $product->save();
            });
        });

        // Seed Variant Products
        $this->seedVariantProducts();

        // Create sample transactions
        $users = User::whereIn('role', ['admin', 'kasir'])->get();

        // Create transactions for the last 30 days
        for ($i = 0; $i < 50; $i++) {
            $transaction = Transaction::create([
                'transaction_code' => 'TRX' . now()->format('Ymd') . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'user_id' => $users->random()->id,
                'subtotal' => 0,
                'discount' => 0,
                'tax' => 0,
                'total_amount' => 0,
                'payment_method' => fake()->randomElement(['cash', 'utang', 'card', 'ewallet', 'transfer']),
                'amount_paid' => 0,
                'change_amount' => 0,
                'status' => 'completed',
                'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
            ]);

            // Add transaction items
            $products = Product::where('stock', '>', 0)->inRandomOrder()->take(random_int(1, 5))->get();
            $subtotal = 0;

            foreach ($products as $product) {
                $quantity = random_int(1, min(3, $product->stock));
                $price = $product->selling_price;
                $itemSubtotal = (float) $price * $quantity;

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $itemSubtotal,
                ]);

                // Update product stock
                $product->decrement('stock', $quantity);

                // Create stock movement
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'out',
                    'quantity' => $quantity,
                    'reference_type' => 'App\Models\Transaction',
                    'reference_id' => $transaction->id,
                    'notes' => "Penjualan - {$transaction->transaction_code}",
                    'created_at' => $transaction->created_at,
                ]);

                $subtotal += $itemSubtotal;
            }

            // Update transaction totals
            $discount = fake()->boolean(30) ? fake()->randomFloat(2, 0, $subtotal * 0.1) : 0;
            $totalAmount = $subtotal - $discount;
            $amountPaid = $totalAmount + fake()->randomFloat(2, 0, 20000);
            $changeAmount = $amountPaid - $totalAmount;

            $transaction->update([
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total_amount' => $totalAmount,
                'amount_paid' => $amountPaid,
                'change_amount' => $changeAmount,
            ]);
        }

        // Create additional stock movements for inventory history
        Product::all()->each(function ($product) {
            // Initial stock entry
            if ($product->stock > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'in',
                    'quantity' => $product->stock + random_int(10, 50),
                    'notes' => 'Stok awal produk',
                    'created_at' => fake()->dateTimeBetween('-60 days', '-30 days'),
                ]);
            }
        });

        // Create expenses and purchases
        $this->call([
            ExpenseSeeder::class,
            PurchaseSeeder::class,
        ]);

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin credentials: admin@minimarket.com / password');
        $this->command->info('Kasir credentials: kasir1@minimarket.com / password');
    }

    private function seedVariantProducts()
    {
        $fashionCategory = Category::where('name', 'Pakaian & Aksesoris')->first();
        if ($fashionCategory) {
            // Product Group 1: T-Shirt
            $tshirtGroup = ProductGroup::create([
                'name' => 'Kaos Polos Premium',
                'category_id' => $fashionCategory->id,
                'description' => 'Kaos polos bahan cotton combed 30s',
                'has_variants' => true,
            ]);

            $colors = ['Hitam', 'Putih', 'Navy'];
            $sizes = ['M', 'L', 'XL'];
            
            foreach ($colors as $color) {
                foreach ($sizes as $size) {
                    Product::create([
                        'product_group_id' => $tshirtGroup->id,
                        'name' => $tshirtGroup->name . " - $color ($size)",
                        'variant_name' => "$color - $size",
                        'category_id' => $fashionCategory->id,
                        'barcode' => 'TS-' . strtoupper(substr($color, 0, 3)) . "-$size-" . fake()->unique()->numerify('###'),
                        'purchase_price' => 45000,
                        'selling_price' => 85000,
                        'stock' => random_int(5, 15),
                        'minimum_stock' => 3,
                    ]);
                }
            }

            // Product Group 2: Pants
            $pantsGroup = ProductGroup::create([
                'name' => 'Celana Chino Panjang',
                'category_id' => $fashionCategory->id,
                'description' => 'Celana chino bahan stretch',
                'has_variants' => true,
            ]);

            $pantsSizes = ['28', '30', '32', '34'];
            foreach ($pantsSizes as $size) {
                Product::create([
                    'product_group_id' => $pantsGroup->id,
                    'name' => $pantsGroup->name . " (Size $size)",
                    'variant_name' => "Size $size",
                    'category_id' => $fashionCategory->id,
                    'barcode' => 'CHINO-' . $size . '-' . fake()->unique()->numerify('###'),
                    'purchase_price' => 110000,
                    'selling_price' => 185000,
                    'stock' => random_int(3, 10),
                    'minimum_stock' => 2,
                ]);
            }
        }
    }
}
