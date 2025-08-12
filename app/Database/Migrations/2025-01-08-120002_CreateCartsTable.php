<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCartsTable extends Migration
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
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true, // null for anonymous sessions
            ],
            'session_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 128,
                'null'       => true, // for anonymous users
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'checkout', 'completed', 'abandoned'],
                'default'    => 'active',
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
        $this->forge->addKey('user_id');
        $this->forge->addKey('session_id');
        $this->forge->addKey('status');
        $this->forge->addKey('created_at');

        $this->forge->createTable('carts');
    }

    public function down()
    {
        $this->forge->dropTable('carts');
    }
}
