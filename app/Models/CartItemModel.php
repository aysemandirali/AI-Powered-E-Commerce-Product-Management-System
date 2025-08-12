<?php

namespace App\Models;

use CodeIgniter\Model;

class CartItemModel extends Model
{
    protected $table = 'cart_items';
    protected $primaryKey = 'id';
    protected $allowedFields = ['cart_id', 'product_id', 'quantity', 'price'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get all items in a cart with product details
     */
    public function getCartItems($cartId): array
    {
        return $this->select('cart_items.*, products.title, products.brand, products.stock, products.price as current_price')
                   ->join('products', 'products.id = cart_items.product_id')
                   ->where('cart_items.cart_id', $cartId)
                   ->where('products.status', 1)
                   ->findAll();
    }

    /**
     * Add item to cart or update quantity if exists
     */
    public function addOrUpdateItem($cartId, $productId, $quantity): array
    {
        // Get product to check stock and current price
        $productModel = new ProductModel();
        $product = $productModel->find($productId);
        
        if (!$product || $product['status'] != 1) {
            return [
                'success' => false,
                'message' => 'Product not found or inactive'
            ];
        }

        // Check stock availability
        if ($product['stock'] < $quantity) {
            return [
                'success' => false,
                'message' => "Only {$product['stock']} items available in stock",
                'max_quantity' => $product['stock']
            ];
        }

        // Check if item already exists in cart
        $existingItem = $this->where('cart_id', $cartId)
                            ->where('product_id', $productId)
                            ->first();

        if ($existingItem) {
            $newQuantity = $existingItem['quantity'] + $quantity;
            
            // Check total quantity against stock
            if ($newQuantity > $product['stock']) {
                $maxAdditional = $product['stock'] - $existingItem['quantity'];
                return [
                    'success' => false,
                    'message' => "Can only add {$maxAdditional} more items (current: {$existingItem['quantity']}, stock: {$product['stock']})",
                    'max_additional' => max(0, $maxAdditional)
                ];
            }
            
            // Update existing item
            $this->update($existingItem['id'], [
                'quantity' => $newQuantity,
                'price' => $product['price'] // Update to current price
            ]);
            
            return [
                'success' => true,
                'message' => 'Cart updated successfully',
                'action' => 'updated',
                'new_quantity' => $newQuantity
            ];
        } else {
            // Add new item
            $itemData = [
                'cart_id' => $cartId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $product['price']
            ];
            
            $itemId = $this->insert($itemData);
            
            return [
                'success' => true,
                'message' => 'Item added to cart successfully',
                'action' => 'added',
                'item_id' => $itemId
            ];
        }
    }

    /**
     * Update item quantity
     */
    public function updateQuantity($cartId, $productId, $quantity): array
    {
        if ($quantity <= 0) {
            return $this->removeItem($cartId, $productId);
        }

        // Get product to check stock
        $productModel = new ProductModel();
        $product = $productModel->find($productId);
        
        if (!$product || $product['status'] != 1) {
            return [
                'success' => false,
                'message' => 'Product not found or inactive'
            ];
        }

        if ($quantity > $product['stock']) {
            return [
                'success' => false,
                'message' => "Only {$product['stock']} items available in stock",
                'max_quantity' => $product['stock']
            ];
        }

        $item = $this->where('cart_id', $cartId)
                    ->where('product_id', $productId)
                    ->first();

        if (!$item) {
            return [
                'success' => false,
                'message' => 'Item not found in cart'
            ];
        }

        $this->update($item['id'], [
            'quantity' => $quantity,
            'price' => $product['price'] // Update to current price
        ]);

        return [
            'success' => true,
            'message' => 'Quantity updated successfully',
            'new_quantity' => $quantity
        ];
    }

    /**
     * Remove item from cart
     */
    public function removeItem($cartId, $productId): array
    {
        $deleted = $this->where('cart_id', $cartId)
                       ->where('product_id', $productId)
                       ->delete();

        if ($deleted) {
            return [
                'success' => true,
                'message' => 'Item removed from cart'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Item not found in cart'
            ];
        }
    }

    /**
     * Get cart item count for a specific cart
     */
    public function getCartItemCount($cartId): int
    {
        $result = $this->selectSum('quantity')
                      ->where('cart_id', $cartId)
                      ->first();
        
        return (int) ($result['quantity'] ?? 0);
    }

    /**
     * Clear all items from cart
     */
    public function clearCart($cartId): bool
    {
        return $this->where('cart_id', $cartId)->delete();
    }

    /**
     * Validate cart items against current stock and prices
     */
    public function validateCartItems($cartId): array
    {
        $items = $this->getCartItems($cartId);
        $issues = [];
        $hasIssues = false;

        foreach ($items as $item) {
            $issue = [];
            
            // Check stock
            if ($item['quantity'] > $item['stock']) {
                $issue['stock'] = "Only {$item['stock']} available (you have {$item['quantity']})";
                $hasIssues = true;
            }
            
            // Check price changes
            if (abs($item['price'] - $item['current_price']) > 0.01) {
                $issue['price'] = "Price changed from {$item['price']} to {$item['current_price']}";
                $hasIssues = true;
            }
            
            if (!empty($issue)) {
                $issues[$item['product_id']] = $issue;
            }
        }

        return [
            'valid' => !$hasIssues,
            'issues' => $issues,
            'items' => $items
        ];
    }
}
