<?php

namespace App\Models;

use CodeIgniter\Model;

class CartModel extends Model
{
    protected $table = 'carts';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'session_id', 'status'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get or create active cart for user/session
     */
    public function getActiveCart($userId = null, $sessionId = null): ?array
    {
        $builder = $this->where('status', 'active');
        
        if ($userId) {
            $builder->where('user_id', $userId);
        } else {
            $builder->where('session_id', $sessionId);
        }
        
        $cart = $builder->first();
        
        if (!$cart) {
            // Create new cart
            $cartData = [
                'user_id' => $userId,
                'session_id' => $sessionId,
                'status' => 'active'
            ];
            
            $cartId = $this->insert($cartData);
            return $this->find($cartId);
        }
        
        return $cart;
    }

    /**
     * Get cart with all items
     */
    public function getCartWithItems($cartId): array
    {
        $cart = $this->find($cartId);
        if (!$cart) {
            return [];
        }

        $cartItemModel = new CartItemModel();
        $items = $cartItemModel->getCartItems($cartId);
        
        return [
            'cart' => $cart,
            'items' => $items,
            'total_quantity' => array_sum(array_column($items, 'quantity')),
            'total_amount' => array_sum(array_map(function($item) {
                return $item['quantity'] * $item['price'];
            }, $items))
        ];
    }

    /**
     * Transfer session cart to user cart when user logs in
     */
    public function transferSessionCart($sessionId, $userId): bool
    {
        // Find session cart
        $sessionCart = $this->where('session_id', $sessionId)
                           ->where('status', 'active')
                           ->first();
        
        if (!$sessionCart) {
            return true; // Nothing to transfer
        }

        // Check if user already has a cart
        $userCart = $this->where('user_id', $userId)
                        ->where('status', 'active')
                        ->first();

        if ($userCart) {
            // Merge session cart items into user cart
            $cartItemModel = new CartItemModel();
            $sessionItems = $cartItemModel->where('cart_id', $sessionCart['id'])->findAll();
            
            foreach ($sessionItems as $item) {
                $cartItemModel->addOrUpdateItem($userCart['id'], $item['product_id'], $item['quantity']);
            }
            
            // Delete session cart
            $this->delete($sessionCart['id']);
        } else {
            // Convert session cart to user cart
            $this->update($sessionCart['id'], [
                'user_id' => $userId,
                'session_id' => null
            ]);
        }
        
        return true;
    }

    /**
     * Clean up old abandoned carts
     */
    public function cleanupOldCarts($daysOld = 30): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));
        
        return $this->where('updated_at <', $cutoffDate)
                   ->where('status', 'active')
                   ->set(['status' => 'abandoned'])
                   ->update();
    }
}
