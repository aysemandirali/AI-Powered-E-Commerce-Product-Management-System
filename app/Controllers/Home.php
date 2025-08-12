<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        // Load the new product management system
        $productController = new ProductController();
        return $productController->index();
    }
    
    public function admin(): string
    {
        // Admin interface for advanced features
        return view('welcome_message');
    }
}
