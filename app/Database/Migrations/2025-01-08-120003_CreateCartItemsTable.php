<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCartItemsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'cart_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'product_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'quantity' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'price' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'unsigned'   => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('cart_id');
        $this->forge->addKey('product_id');
        $this->forge->addKey(['cart_id', 'product_id']); // Composite for uniqueness

        $this->forge->createTable('cart_items');

        // Add foreign key constraints
        $this->db->query('ALTER TABLE cart_items ADD CONSTRAINT fk_cart_items_cart FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE cart_items ADD CONSTRAINT fk_cart_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down()
    {
        $this->forge->dropTable('cart_items');
    }
}
