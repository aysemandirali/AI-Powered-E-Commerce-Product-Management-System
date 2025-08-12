<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\CategoryModel;
use App\Models\CartModel;
use App\Models\CartItemModel;
use App\Libraries\GeminiService;

class ProductController extends BaseController
{
    protected $productModel;
    protected $categoryModel;
    protected $cartModel;
    protected $cartItemModel;
    protected $geminiService;

    public function __construct()
    {
        $this->productModel = new ProductModel();
        $this->categoryModel = new CategoryModel();
        $this->cartModel = new CartModel();
        $this->cartItemModel = new CartItemModel();
        $this->geminiService = new GeminiService();
    }

    /**
     * Main product management interface
     */
    public function index()
    {
        $data = [
            'title' => 'AI-Powered Product Management',
            'categories' => $this->categoryModel->getMockCategories(),
            'cart_count' => $this->getCartItemCount()
        ];

        return view('product_management', $data);
    }

    /**
     * Save new product or update existing
     */
    public function save()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $rules = [
            'title' => 'required|min_length[3]|max_length[255]',
            'brand' => 'required|min_length[2]|max_length[100]',
            'category_id' => 'required|integer',
            'description' => 'required|min_length[10]',
            'price' => 'required|decimal|greater_than[0]',
            'stock' => 'required|integer|greater_than_equal_to[0]'
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors()
            ]);
        }

        $productId = $this->request->getPost('id');
        $data = [
            'category_id' => $this->request->getPost('category_id'),
            'brand' => trim($this->request->getPost('brand')),
            'title' => trim($this->request->getPost('title')),
            'description' => trim($this->request->getPost('description')),
            'meta_seo' => trim($this->request->getPost('meta_seo') ?? ''),
            'features' => trim($this->request->getPost('features') ?? ''),
            'price' => floatval($this->request->getPost('price')),
            'stock' => intval($this->request->getPost('stock')),
            'status' => 1
        ];

        try {
            if ($productId) {
                // Update existing product
                $result = $this->productModel->update($productId, $data);
                if ($result === false) {
                    throw new \Exception('Failed to update product in database');
                }
                $message = "Product updated successfully (ID: {$productId})";
                $action = 'updated';
                        } else {
                // Create new product - generate unique slug
                $data['slug'] = $this->productModel->generateSlug($data['title']);

                log_message('debug', "Attempting to insert product with slug: {$data['slug']}");

                $productId = $this->productModel->insert($data);

                if ($productId === false) {
                    throw new \Exception('Failed to insert product into database');
                }

                $message = "Product created successfully (ID: {$productId})";
                $action = 'created';
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => $message,
                'product_id' => $productId,
                'action' => $action,
                'slug' => $data['slug'] ?? null
            ]);

                } catch (\Exception $e) {
            $fullError = $e->getMessage();
            log_message('error', 'Product save error: ' . $fullError);
            log_message('error', 'Attempted data: ' . json_encode($data));

            // Analyze the specific error
            $errorMessage = $fullError;
            $errorType = 'unknown';

            if (strpos($fullError, 'Duplicate entry') !== false) {
                if (strpos($fullError, 'slug') !== false) {
                    $errorMessage = 'Product slug conflict detected';
                    $errorType = 'slug_duplicate';
                } elseif (strpos($fullError, 'title') !== false) {
                    $errorMessage = 'Product title already exists';
                    $errorType = 'title_duplicate';
                } else {
                    $errorMessage = 'Duplicate entry detected: ' . $fullError;
                    $errorType = 'other_duplicate';
                }
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to save product: ' . $errorMessage,
                'error_details' => $fullError,
                'error_type' => $errorType,
                'attempted_data' => $data,
                'generated_slug' => $data['slug'] ?? 'not_generated'
            ]);
        }
    }

    /**
     * Validate individual field with AI
     */
    public function validateField()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $fieldName = $this->request->getPost('field');
        $content = $this->request->getPost('content');
        $category = $this->request->getPost('category');

        if (empty($fieldName) || empty($content)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Field name and content are required'
            ]);
        }

        try {
            $result = $this->geminiService->validateField($fieldName, $content, $category);
            return $this->response->setJSON($result);
        } catch (\Exception $e) {
            log_message('error', 'Field validation error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validation service temporarily unavailable'
            ]);
        }
    }

    /**
     * AI-powered product search
     */
    public function search()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $query = trim($this->request->getPost('query') ?? '');
        $limit = intval($this->request->getPost('limit') ?? 20);

        if (empty($query)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Search query is required'
            ]);
        }

        try {
            // Always use basic search first for reliability
            $products = $this->productModel->searchProducts($query, $limit);

            // Try AI parsing as enhancement, but don't rely on it
            $parseResult = null;
            try {
                $parseResult = $this->geminiService->parseSearchQuery($query);
                if ($parseResult['success'] && !empty($parseResult['extracted_filters'])) {
                    // Try enhanced search with filters
                    $enhancedProducts = $this->productModel->searchProductsWithFilters(
                        $query,
                        $parseResult['extracted_filters'],
                        $limit
                    );
                    // Use enhanced results if they return more results
                    if (count($enhancedProducts) >= count($products)) {
                        $products = $enhancedProducts;
                    }
                }
            } catch (\Exception $aiError) {
                log_message('warning', 'AI search failed, using basic search: ' . $aiError->getMessage());
            }

            return $this->response->setJSON([
                'success' => true,
                'query' => $query,
                'ai_parsing' => $parseResult,
                'products' => $products,
                'total_found' => count($products),
                'search_method' => $parseResult ? 'enhanced' : 'basic'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Search error: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'query' => $query,
                'message' => 'Search failed: ' . $e->getMessage(),
                'products' => [],
                'total_found' => 0
            ]);
        }
    }

    /**
     * Get product details for editing
     */
    public function getProduct($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $product = $this->productModel->getProductWithCategory($id);

        if (!$product) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Product not found'
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'product' => $product
        ]);
    }

    /**
     * Add product to cart
     */
    public function addToCart()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $productId = intval($this->request->getPost('product_id'));
        $quantity = intval($this->request->getPost('quantity') ?? 1);

        if ($productId <= 0 || $quantity <= 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid product or quantity'
            ]);
        }

        try {
            // Get or create cart
            $userId = null; // TODO: Get from session when user auth is implemented
            $sessionId = session_id();

            $cart = $this->cartModel->getActiveCart($userId, $sessionId);

            // Add item to cart
            $result = $this->cartItemModel->addOrUpdateItem($cart['id'], $productId, $quantity);

            if ($result['success']) {
                $result['cart_count'] = $this->cartItemModel->getCartItemCount($cart['id']);
            }

            return $this->response->setJSON($result);

        } catch (\Exception $e) {
            log_message('error', 'Add to cart error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to add item to cart'
            ]);
        }
    }

    /**
     * Get current cart item count
     */
    public function getCartCount()
    {
        try {
            $count = $this->getCartItemCount();
            return $this->response->setJSON([
                'success' => true,
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => true,
                'count' => 0
            ]);
        }
    }

    /**
     * Get cart contents with full details
     */
    public function getCartContents()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        try {
            $userId = null; // TODO: Get from session when user auth is implemented
            $sessionId = session_id();

            // Get active cart
            $cart = $this->cartModel->where('status', 'active');
            if ($userId) {
                $cart->where('user_id', $userId);
            } else {
                $cart->where('session_id', $sessionId);
            }

            $cartData = $cart->first();

            if (!$cartData) {
                return $this->response->setJSON([
                    'success' => true,
                    'cart' => [],
                    'total_items' => 0,
                    'total_amount' => 0,
                    'message' => 'Cart is empty'
                ]);
            }

            // Get cart with all items
            $cartWithItems = $this->cartModel->getCartWithItems($cartData['id']);

            return $this->response->setJSON([
                'success' => true,
                'cart' => $cartWithItems,
                'total_items' => $cartWithItems['total_quantity'],
                'total_amount' => $cartWithItems['total_amount']
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Get cart contents error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load cart contents'
            ]);
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateCartItem()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $productId = intval($this->request->getPost('product_id'));
        $quantity = intval($this->request->getPost('quantity'));

        if ($productId <= 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid product ID'
            ]);
        }

        try {
            $userId = null; // TODO: Get from session when user auth is implemented
            $sessionId = session_id();

            $cart = $this->cartModel->getActiveCart($userId, $sessionId);

            if ($quantity <= 0) {
                // Remove item
                $result = $this->cartItemModel->removeItem($cart['id'], $productId);
            } else {
                // Update quantity
                $result = $this->cartItemModel->updateQuantity($cart['id'], $productId, $quantity);
            }

            if ($result['success']) {
                $result['cart_count'] = $this->cartItemModel->getCartItemCount($cart['id']);
            }

            return $this->response->setJSON($result);

        } catch (\Exception $e) {
            log_message('error', 'Update cart item error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to update cart item'
            ]);
        }
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $productId = intval($this->request->getPost('product_id'));

        if ($productId <= 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid product ID'
            ]);
        }

        try {
            $userId = null; // TODO: Get from session when user auth is implemented
            $sessionId = session_id();

            $cart = $this->cartModel->getActiveCart($userId, $sessionId);
            $result = $this->cartItemModel->removeItem($cart['id'], $productId);

            if ($result['success']) {
                $result['cart_count'] = $this->cartItemModel->getCartItemCount($cart['id']);
            }

            return $this->response->setJSON($result);

        } catch (\Exception $e) {
            log_message('error', 'Remove from cart error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to remove item from cart'
            ]);
        }
    }

    /**
     * Analyze complete product with AI
     */
    public function analyzeProduct()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $productData = [
            'title' => $this->request->getPost('title'),
            'brand' => $this->request->getPost('brand'),
            'category' => $this->request->getPost('category'),
            'description' => $this->request->getPost('description'),
            'features' => $this->request->getPost('features'),
            'price' => $this->request->getPost('price'),
            'stock' => $this->request->getPost('stock'),
            'meta_seo' => $this->request->getPost('meta_seo')
        ];

        try {
            $analysis = $this->geminiService->analyzeProduct($productData);
            return $this->response->setJSON($analysis);
        } catch (\Exception $e) {
            log_message('error', 'Product analysis error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Analysis service temporarily unavailable'
            ]);
        }
    }

    /**
     * Helper method to get cart item count
     */
    private function getCartItemCount(): int
    {
        try {
            $userId = null; // TODO: Get from session when user auth is implemented
            $sessionId = session_id();

            $cart = $this->cartModel->where('status', 'active');

            if ($userId) {
                $cart->where('user_id', $userId);
            } else {
                $cart->where('session_id', $sessionId);
            }

            $cartData = $cart->first();

            if (!$cartData) {
                return 0;
            }

            return $this->cartItemModel->getCartItemCount($cartData['id']);
        } catch (\Exception $e) {
            return 0;
        }
    }
}
