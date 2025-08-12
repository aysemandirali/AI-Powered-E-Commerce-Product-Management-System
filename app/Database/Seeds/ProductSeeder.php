<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $products = [
            // Electronics
            [
                'category_id' => 1,
                'brand' => 'Apple',
                'title' => 'iPhone 15 Pro Max',
                'slug' => 'iphone-15-pro-max',
                'description' => 'Latest Apple iPhone with advanced camera system and A17 Pro chip. Premium titanium design with ProRAW photography capabilities.',
                'meta_seo' => 'Buy iPhone 15 Pro Max - Latest Apple smartphone with advanced features',
                'features' => 'A17 Pro chip, 48MP camera system, Titanium design, 5G connectivity, Face ID',
                'price' => 1199.99,
                'stock' => 50,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'category_id' => 1,
                'brand' => 'Samsung',
                'title' => 'Samsung Galaxy S24 Ultra',
                'slug' => 'samsung-galaxy-s24-ultra',
                'description' => 'Powerful Android smartphone with S Pen, 200MP camera, and AI features. Perfect for productivity and creativity.',
                'meta_seo' => 'Samsung Galaxy S24 Ultra - Premium Android phone with S Pen',
                'features' => 'Snapdragon 8 Gen 3, S Pen, 200MP camera, 6.8" Dynamic AMOLED display',
                'price' => 1299.99,
                'stock' => 30,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            
            // Clothing
            [
                'category_id' => 2,
                'brand' => 'Nike',
                'title' => 'Men\'s Cotton T-Shirt Navy Blue',
                'slug' => 'mens-cotton-tshirt-navy-blue',
                'description' => 'Comfortable cotton t-shirt for men in navy blue. Perfect for casual wear and everyday activities.',
                'meta_seo' => 'Men\'s Navy Blue Cotton T-Shirt - Comfortable casual wear',
                'features' => '100% cotton, Machine washable, Crew neck, Regular fit',
                'price' => 29.99,
                'stock' => 100,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'category_id' => 2,
                'brand' => 'Adidas',
                'title' => 'Women\'s White Cotton Office Blouse',
                'slug' => 'womens-white-cotton-office-blouse',
                'description' => 'Professional white cotton blouse for women. Elegant design suitable for office and formal occasions.',
                'meta_seo' => 'Women\'s White Office Blouse - Professional cotton shirt',
                'features' => 'Cotton blend, Button-down, Collar, Long sleeves, Professional fit',
                'price' => 49.99,
                'stock' => 75,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            
            // Home & Garden
            [
                'category_id' => 3,
                'brand' => 'IKEA',
                'title' => 'Modern Wooden Coffee Table',
                'slug' => 'modern-wooden-coffee-table',
                'description' => 'Stylish wooden coffee table with minimalist design. Perfect for modern living rooms.',
                'meta_seo' => 'Modern Wooden Coffee Table - Stylish living room furniture',
                'features' => 'Solid wood, Modern design, Easy assembly, Scratch resistant',
                'price' => 199.99,
                'stock' => 25,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            
            // Sports & Outdoors
            [
                'category_id' => 4,
                'brand' => 'Wilson',
                'title' => 'Professional Tennis Racket',
                'slug' => 'professional-tennis-racket',
                'description' => 'High-quality tennis racket for professional and amateur players. Excellent control and power.',
                'meta_seo' => 'Professional Tennis Racket - High-quality sports equipment',
                'features' => 'Carbon fiber frame, Professional grip, 27 inches, 300g weight',
                'price' => 159.99,
                'stock' => 40,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            
            // Books
            [
                'category_id' => 5,
                'brand' => 'Penguin Books',
                'title' => 'The Art of Computer Programming',
                'slug' => 'art-of-computer-programming',
                'description' => 'Classic computer science book by Donald Knuth. Essential reading for programmers and computer scientists.',
                'meta_seo' => 'The Art of Computer Programming - Classic CS book by Donald Knuth',
                'features' => 'Hardcover, 650 pages, Algorithms, Programming techniques',
                'price' => 79.99,
                'stock' => 60,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        // Insert products
        $this->db->table('products')->insertBatch($products);
    }
}