<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\CategoryModel;
use App\Libraries\GeminiService;

class AI extends BaseController
{
    protected $productModel;
    protected $categoryModel;
    protected $geminiService;

    public function __construct()
    {
        $this->productModel = new ProductModel();
        $this->categoryModel = new CategoryModel();
        $this->geminiService = new GeminiService();
    }

    public function search()
    {
        $query = $this->request->getPost('query');
        
        if (!$query) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Query is required'
            ]);
        }

        try {
            // Use Gemini to parse natural language query into structured filters
            $geminiResult = $this->geminiService->parseSearchQuery($query);
            
            // Search products using both AI filters and fallback keyword search
            $products = $this->productModel->searchProductsWithFilters(
                $query, 
                $geminiResult['extracted_filters'] ?? [], 
                10
            );
            
            return $this->response->setJSON([
                'success' => true,
                'products' => $products,
                'query' => $query,
                'gemini_analysis' => $geminiResult,
                'total_results' => count($products)
            ]);
            
        } catch (\Exception $e) {
            // Fallback to basic search if AI fails
            $products = $this->productModel->searchProducts($query, 10);
            
            return $this->response->setJSON([
                'success' => true,
                'products' => $products,
                'query' => $query,
                'gemini_analysis' => [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'fallback_used' => true
                ],
                'total_results' => count($products)
            ]);
        }
    }
    
    /**
     * Validate individual product field using Gemini AI
     */
    public function validateField()
    {
        $fieldName = $this->request->getPost('field_name');
        $content = $this->request->getPost('content');
        $category = $this->request->getPost('category');
        
        if (!$fieldName || !$content) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Field name and content are required'
            ]);
        }

        try {
            $result = $this->geminiService->validateField($fieldName, $content, $category);
            return $this->response->setJSON($result);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'field' => $fieldName,
                'error' => 'Validation failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Analyze complete product using Gemini AI
     */
    public function analyze()
    {
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
            $result = $this->geminiService->analyzeProduct($productData);
            return $this->response->setJSON($result);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Analysis failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get search suggestions based on partial query
     */
    public function suggest()
    {
        $partialQuery = $this->request->getPost('query');
        
        if (empty($partialQuery) || strlen($partialQuery) < 2) {
            return $this->response->setJSON([
                'success' => true,
                'suggestions' => []
            ]);
        }

        try {
            // Get popular search terms and product suggestions
            $suggestions = $this->productModel->getSearchSuggestions($partialQuery, 5);
            
            return $this->response->setJSON([
                'success' => true,
                'suggestions' => $suggestions,
                'query' => $partialQuery
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Demo page for AI-powered search
     */
    public function demo()
    {
        return view('ai_search_demo');
    }

    /**
     * Gemini API test page
     */
    public function geminiTest()
    {
        return view('gemini_test');
    }

    /**
     * Test Gemini API connection and configuration
     */
    public function test()
    {
        $apiConfigured = $this->geminiService->isConfigured();
        
        if (!$apiConfigured) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gemini API key not configured',
                'gemini_configured' => false,
                'error' => 'API key is missing or invalid',
                'status' => 'offline'
            ]);
        }

        try {
            // Simple test with minimal request
            $testQuery = "test";
            $result = $this->geminiService->parseSearchQuery($testQuery);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Gemini API is working perfectly!',
                'gemini_configured' => true,
                'status' => 'online',
                'test_result' => $result['success'] ?? false,
                'ai_ready' => true
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Gemini API test failed: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gemini API test failed',
                'gemini_configured' => true,
                'status' => 'error',
                'error' => $e->getMessage(),
                'fallback_available' => true
            ]);
        }
    }
}