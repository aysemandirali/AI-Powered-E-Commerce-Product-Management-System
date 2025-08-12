<?php
namespace App\Models;
use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'category_id', 'brand', 'title', 'description',
        'meta_seo', 'features', 'price', 'stock', 'status', 'slug'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

        // Search products with AI-like functionality
    public function searchProducts($query, $limit = 10)
    {
        if (empty($query)) {
            return $this->where('status', 1)->limit($limit)->findAll();
        }

        $builder = $this->select('products.*, categories.name as category_name')
                        ->join('categories', 'categories.id = products.category_id', 'left')
                        ->where('products.status', 1);

        // Simple but effective search across all fields
        $builder->groupStart();

        // Search in title
        $builder->like('products.title', $query);

        // Search in brand
        $builder->orLike('products.brand', $query);

        // Search in description
        $builder->orLike('products.description', $query);

        // Search in features
        $builder->orLike('products.features', $query);

        // Search in category name
        $builder->orLike('categories.name', $query);

        $builder->groupEnd();

        return $builder->limit($limit)->findAll();
    }

        // Enhanced search with AI-extracted filters
    public function searchProductsWithFilters($query, $filters = [], $limit = 10)
    {
        $builder = $this->select('products.*, categories.name as category_name')
                        ->join('categories', 'categories.id = products.category_id', 'left')
                        ->where('products.status', 1);

        // Apply basic search query first
        if (!empty($query)) {
            $builder->groupStart();
            $builder->like('products.title', $query)
                    ->orLike('products.brand', $query)
                    ->orLike('products.description', $query)
                    ->orLike('products.features', $query)
                    ->orLike('categories.name', $query);
            $builder->groupEnd();
        }

        // Apply filters if available
        if (!empty($filters['brand'])) {
            $builder->like('products.brand', $filters['brand']);
        }

        if (!empty($filters['category'])) {
            $builder->like('categories.name', $filters['category']);
        }

        if (!empty($filters['keywords']) && is_array($filters['keywords'])) {
            $builder->groupStart();
            foreach ($filters['keywords'] as $keyword) {
                if (strlen(trim($keyword)) > 2) {
                    $builder->orLike('products.title', $keyword)
                            ->orLike('products.brand', $keyword)
                            ->orLike('products.description', $keyword)
                            ->orLike('products.features', $keyword);
                }
            }
            $builder->groupEnd();
        }

        return $builder->limit($limit)->findAll();
    }

    // Debug search function
    public function debugSearch($query, $limit = 10)
    {
        $builder = $this->select('products.*, categories.name as category_name')
                        ->join('categories', 'categories.id = products.category_id', 'left')
                        ->where('products.status', 1);

        if (!empty($query)) {
            $builder->groupStart();
            $builder->like('products.title', $query)
                    ->orLike('products.brand', $query)
                    ->orLike('products.description', $query)
                    ->orLike('products.features', $query)
                    ->orLike('categories.name', $query);
            $builder->groupEnd();
        }

        // Get the SQL query for debugging
        $sql = $builder->getCompiledSelect(false);
        log_message('debug', "Search SQL for query '{$query}': " . $sql);

        $results = $builder->limit($limit)->findAll();
        log_message('debug', "Search results count: " . count($results));

        return $results;
    }

    // Get search suggestions
    public function getSearchSuggestions($query, $limit = 5)
    {
        if (empty($query)) return [];

        // Get matching titles and brands
        $products = $this->select('title, brand')
                         ->where('status', 1)
                         ->groupStart()
                             ->like('title', $query)
                             ->orLike('brand', $query)
                         ->groupEnd()
                         ->limit($limit * 2)
                         ->findAll();

        $suggestions = [];
        foreach ($products as $product) {
            if (stripos($product['title'], $query) !== false && !in_array($product['title'], $suggestions)) {
                $suggestions[] = $product['title'];
            }
            if (stripos($product['brand'], $query) !== false && !in_array($product['brand'], $suggestions)) {
                $suggestions[] = $product['brand'];
            }
        }

        return array_slice(array_unique($suggestions), 0, intval($limit));
    }

    // Get product with category
    public function getProductWithCategory($id)
    {
        return $this->select('products.*, categories.name as category_name')
                    ->join('categories', 'categories.id = products.category_id', 'left')
                    ->find($id);
    }

    // Get products by category
    public function getByCategory($categoryId, $limit = null)
    {
        $builder = $this->where('category_id', $categoryId)
                        ->where('status', 1);

        if ($limit) {
            $builder->limit($limit);
        }

        return $builder->findAll();
    }

        // Generate unique slug - simplified and reliable
    public function generateSlug($title, $id = null)
    {
        // Create base slug
        $baseSlug = url_title($title, '-', true);

        // Always add timestamp and random to ensure uniqueness
        $timestamp = time();
        $random = rand(100, 999);
        $slug = $baseSlug . '-' . $timestamp . '-' . $random;

        // Double check for absolute uniqueness
        $counter = 1;
        $originalSlug = $slug;

        while ($counter <= 10) {
            $query = $this->db->table('products')
                             ->select('id')
                             ->where('slug', $slug);

            if ($id) {
                $query->where('id !=', $id);
            }

            $result = $query->get();

            if ($result->getNumRows() == 0) {
                // Slug is available
                log_message('debug', "Generated unique slug: {$slug}");
                return $slug;
            }

            // If still exists, add more randomness
            $slug = $originalSlug . '-' . $counter . '-' . rand(10, 99);
            $counter++;
        }

        // Final fallback
        return $baseSlug . '-' . uniqid() . '-' . rand(1000, 9999);
    }



    // Get real categories from database
    public function getRealCategories()
    {
        $categoryModel = new \App\Models\CategoryModel();
        return $categoryModel->where('status', 1)->findAll();
    }


}
