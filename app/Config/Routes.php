<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// NEW AI-POWERED PRODUCT MANAGEMENT SYSTEM
$routes->get('/', 'ProductController::index');

// Product Management Routes
$routes->group('product', function($routes) {
    $routes->get('/', 'ProductController::index');
    $routes->post('save', 'ProductController::save');
    $routes->post('validateField', 'ProductController::validateField');
    $routes->post('search', 'ProductController::search');
    $routes->get('getProduct/(:num)', 'ProductController::getProduct/$1');
    $routes->post('addToCart', 'ProductController::addToCart');
    $routes->get('getCartCount', 'ProductController::getCartCount');
    $routes->get('getCartContents', 'ProductController::getCartContents');
    $routes->post('updateCartItem', 'ProductController::updateCartItem');
    $routes->post('removeFromCart', 'ProductController::removeFromCart');
    $routes->post('analyzeProduct', 'ProductController::analyzeProduct');
});

// OLD SYSTEM REDIRECTS (for backward compatibility)
$routes->get('urun-ekle', 'ProductController::index');
$routes->get('product/add', 'ProductController::index');
