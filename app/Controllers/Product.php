<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\CategoryModel;
use App\Models\ValidationModel;

class Product extends BaseController
{
    protected $productModel;
    protected $categoryModel;
    protected $validationModel;

    public function __construct()
    {
        $this->productModel = new ProductModel();
        $this->categoryModel = new CategoryModel();
        $this->validationModel = new ValidationModel();
    }

    public function add()
    {
        $data['categories'] = $this->categoryModel->getCategoriesForDropdown();
        return view('product_add', $data);
    }

    public function save()
    {
        $validation = \Config\Services::validation();
        
        $rules = [
            'title' => 'required|min_length[3]|max_length[255]',
            'brand' => 'required|min_length[2]|max_length[100]',
            'category_id' => 'required|integer',
            'description' => 'required|min_length[10]',
            'price' => 'required|decimal|greater_than[0]',
            'stock' => 'required|integer|greater_than_equal_to[0]',
            'features' => 'permit_empty|max_length[1000]',
            'meta_seo' => 'permit_empty|max_length[500]'
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validation errors occurred',
                'errors' => $validation->getErrors()
            ]);
        }

        $data = [
            'title' => $this->request->getPost('title'),
            'brand' => $this->request->getPost('brand'),
            'category_id' => $this->request->getPost('category_id'),
            'description' => $this->request->getPost('description'),
            'price' => $this->request->getPost('price'),
            'stock' => $this->request->getPost('stock'),
            'features' => $this->request->getPost('features'),
            'meta_seo' => $this->request->getPost('meta_seo'),
            'status' => 1
        ];

        try {
            $productId = $this->productModel->insert($data);
            
            if ($productId) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Product saved successfully!',
                    'product_id' => $productId
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to save product'
                ]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function validateField()
    {
        $fieldName = $this->request->getPost('field_name');
        $fieldValue = $this->request->getPost('field_value');
        $productId = $this->request->getPost('product_id') ?? 0;

        $isValid = false;
        $message = '';

        switch ($fieldName) {
            case 'title':
                if (strlen($fieldValue) >= 3 && strlen($fieldValue) <= 255) {
                    $isValid = true;
                    $message = 'Title is valid';
                } else {
                    $message = 'Title must be between 3-255 characters';
                }
                break;

            case 'brand':
                if (strlen($fieldValue) >= 2 && strlen($fieldValue) <= 100) {
                    $isValid = true;
                    $message = 'Brand is valid';
                } else {
                    $message = 'Brand must be between 2-100 characters';
                }
                break;

            case 'category_id':
                if (is_numeric($fieldValue) && $fieldValue > 0) {
                    $category = $this->categoryModel->find($fieldValue);
                    if ($category) {
                        $isValid = true;
                        $message = 'Category is valid';
                    } else {
                        $message = 'Selected category does not exist';
                    }
                } else {
                    $message = 'Please select a valid category';
                }
                break;

            case 'description':
                if (strlen($fieldValue) >= 10) {
                    $isValid = true;
                    $message = 'Description is valid';
                } else {
                    $message = 'Description must be at least 10 characters long';
                }
                break;

            case 'price':
                if (is_numeric($fieldValue) && $fieldValue > 0) {
                    $isValid = true;
                    $message = 'Price is valid';
                } else {
                    $message = 'Price must be a positive number';
                }
                break;

            case 'stock':
                if (is_numeric($fieldValue) && $fieldValue >= 0) {
                    $isValid = true;
                    $message = 'Stock quantity is valid';
                } else {
                    $message = 'Stock must be a non-negative number';
                }
                break;

            default:
                $message = 'Unknown field';
        }

        // Save validation result
        if ($productId > 0) {
            $this->validationModel->saveValidation($productId, $fieldName, $isValid, $message, 'system');
        }

        return $this->response->setJSON([
            'is_valid' => $isValid,
            'message' => $message,
            'field_name' => $fieldName
        ]);
    }

    public function get($id)
    {
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

    public function update($id)
    {
        $product = $this->productModel->find($id);
        
        if (!$product) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Product not found'
            ]);
        }

        $validation = \Config\Services::validation();
        
        $rules = [
            'title' => 'required|min_length[3]|max_length[255]',
            'category_id' => 'required|integer',
            'description' => 'required|min_length[10]',
            'price' => 'required|decimal|greater_than[0]',
            'stock' => 'required|integer|greater_than_equal_to[0]'
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validation errors occurred',
                'errors' => $validation->getErrors()
            ]);
        }

        $data = [
            'title' => $this->request->getPost('title'),
            'category_id' => $this->request->getPost('category_id'),
            'description' => $this->request->getPost('description'),
            'price' => $this->request->getPost('price'),
            'stock' => $this->request->getPost('stock'),
            'features' => $this->request->getPost('features'),
            'meta_seo' => $this->request->getPost('meta_seo')
        ];

        // Generate new slug if title changed
        if ($data['title'] !== $product['title']) {
            $data['slug'] = $this->productModel->generateSlug($data['title'], $id);
        }

        try {
            if ($this->productModel->update($id, $data)) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Product updated successfully!'
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to update product'
                ]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function delete($id)
    {
        $product = $this->productModel->find($id);
        
        if (!$product) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Product not found'
            ]);
        }

        try {
            if ($this->productModel->delete($id)) {
                // Also delete related validations
                $this->validationModel->where('product_id', $id)->delete();
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Product deleted successfully!'
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to delete product'
                ]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function list()
    {
        $limit = $this->request->getGet('limit') ?? 10;
        $offset = $this->request->getGet('offset') ?? 0;
        $search = $this->request->getGet('search');

        $builder = $this->productModel->select('products.*, categories.name as category_name')
                                     ->join('categories', 'categories.id = products.category_id', 'left');

        if ($search) {
            $builder->groupStart()
                    ->like('products.title', $search)
                    ->orLike('products.description', $search)
                    ->orLike('categories.name', $search)
                    ->groupEnd();
        }

        $products = $builder->limit($limit, $offset)
                           ->orderBy('products.created_at', 'DESC')
                           ->findAll();

        $totalCount = $this->productModel->countAllResults();

        return $this->response->setJSON([
            'success' => true,
            'products' => $products,
            'total' => $totalCount,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }
}
