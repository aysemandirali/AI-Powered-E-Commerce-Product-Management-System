<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - E-commerce Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-plus-circle"></i> Add New Product</h1>
            <p>Create and manage your e-commerce product catalog with AI assistance</p>
        </div>

        <!-- Alert Container -->
        <div id="alertContainer"></div>

        <!-- Main Card -->
        <div class="main-card">
            <div class="card-header">
                <h2><i class="fas fa-box"></i> Product Information</h2>
            </div>
            
            <div class="card-body">
                <form id="productForm">
                    <div class="row">
                        <!-- Product Title -->
                        <div class="col-md-6 mb-3">
                            <label for="title" class="form-label">
                                <i class="fas fa-heading"></i> Product Title *
                            </label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control" 
                                       id="title" 
                                       name="title" 
                                       placeholder="Enter product title (e.g., Premium Wireless Headphones)"
                                       maxlength="255"
                                       required>
                                <button type="button" class="btn btn-outline-info validate-field-btn" data-field="title">
                                    <i class="fas fa-check"></i> Validate
                                </button>
                            </div>
                            <small class="form-text text-muted">Enter a clear, descriptive title (3-255 characters)</small>
                        </div>

                        <!-- Brand -->
                        <div class="col-md-3 mb-3">
                            <label for="brand" class="form-label">
                                <i class="fas fa-award"></i> Brand *
                            </label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control" 
                                       id="brand" 
                                       name="brand" 
                                       placeholder="e.g., Apple, Nike, Samsung"
                                       maxlength="100"
                                       required>
                                <button type="button" class="btn btn-outline-info validate-field-btn" data-field="brand">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">Brand name (2-100 characters)</small>
                        </div>

                        <!-- Category -->
                        <div class="col-md-3 mb-3">
                            <label for="category_id" class="form-label">
                                <i class="fas fa-tags"></i> Category *
                            </label>
                            <div class="input-group">
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php if (isset($categories) && is_array($categories)): ?>
                                        <?php foreach ($categories as $id => $name): ?>
                                            <option value="<?= esc($id) ?>"><?= esc($name) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <button type="button" class="btn btn-outline-info validate-field-btn" data-field="category_id">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">Choose the category</small>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Price -->
                        <div class="col-md-4 mb-3">
                            <label for="price" class="form-label">
                                <i class="fas fa-dollar-sign"></i> Price *
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" 
                                       class="form-control" 
                                       id="price" 
                                       name="price" 
                                       placeholder="0.00"
                                       step="0.01"
                                       min="0.01"
                                       required>
                                <button type="button" class="btn btn-outline-info validate-field-btn" data-field="price">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">Set your product price (minimum $0.01)</small>
                        </div>

                        <!-- Stock -->
                        <div class="col-md-4 mb-3">
                            <label for="stock" class="form-label">
                                <i class="fas fa-boxes"></i> Stock Quantity
                            </label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control" 
                                       id="stock" 
                                       name="stock" 
                                       placeholder="0"
                                       min="0"
                                       value="0">
                                <button type="button" class="btn btn-outline-info validate-field-btn" data-field="stock">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">Available quantity (0 or higher)</small>
                        </div>

                        <!-- Status (Hidden, always active for new products) -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                <i class="fas fa-toggle-on"></i> Status
                            </label>
                            <div class="form-control-plaintext">
                                <span class="badge bg-success">
                                    <i class="fas fa-check-circle"></i> Active
                                </span>
                            </div>
                            <small class="form-text text-muted">New products are automatically set to active</small>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">
                            <i class="fas fa-align-left"></i> Product Description *
                        </label>
                        <div class="input-group">
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="4" 
                                      placeholder="Provide a detailed description of your product. Include key features, specifications, and benefits."
                                      required></textarea>
                            <button type="button" class="btn btn-outline-info validate-field-btn" data-field="description">
                                <i class="fas fa-check"></i> Validate
                            </button>
                        </div>
                        <small class="form-text text-muted">Detailed description helps customers understand your product (minimum 10 characters)</small>
                    </div>

                    <!-- Features -->
                    <div class="mb-3">
                        <label for="features" class="form-label">
                            <i class="fas fa-list-ul"></i> Key Features
                        </label>
                        <div class="input-group">
                            <textarea class="form-control" 
                                      id="features" 
                                      name="features" 
                                      rows="3" 
                                      placeholder="• Feature 1&#10;• Feature 2&#10;• Feature 3&#10;List the main features and benefits of your product"></textarea>
                            <button type="button" class="btn btn-outline-info validate-field-btn" data-field="features">
                                <i class="fas fa-check"></i> Validate
                            </button>
                        </div>
                        <small class="form-text text-muted">List key features that make your product stand out (optional)</small>
                    </div>

                    <!-- Meta SEO -->
                    <div class="mb-4">
                        <label for="meta_seo" class="form-label">
                            <i class="fas fa-search"></i> SEO Meta Description
                        </label>
                        <div class="input-group">
                            <textarea class="form-control" 
                                      id="meta_seo" 
                                      name="meta_seo" 
                                      rows="2" 
                                      placeholder="Write a compelling meta description for search engines (150-160 characters recommended)"
                                      maxlength="500"></textarea>
                            <button type="button" class="btn btn-outline-info validate-field-btn" data-field="meta_seo">
                                <i class="fas fa-check"></i> Validate
                            </button>
                        </div>
                        <small class="form-text text-muted">Optimize for search engines to improve product visibility (optional)</small>
                    </div>

                    <!-- Action Buttons -->
                    <div class="btn-group">
                        <button type="button" id="aiAssistant" class="btn btn-info">
                            <i class="fas fa-robot"></i> AI Assistant
                        </button>
                        
                        <button type="button" id="validateProduct" class="btn btn-warning">
                            <i class="fas fa-check-double"></i> Validate All Fields
                        </button>
                        
                        <button type="submit" id="saveProduct" class="btn btn-success">
                            <i class="fas fa-save"></i> Save Product
                        </button>
                        
                        <button type="button" id="addNewProduct" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Add New Product
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-4 mb-3">
            <p class="text-white-50">
                <i class="fas fa-shield-alt"></i> Secure Product Management System
            </p>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?= base_url('assets/js/main.js') ?>"></script>
    
    <!-- Page-specific script -->
    <script>
        // Initialize page
        $(document).ready(function() {
            console.log('Product Add Page Loaded Successfully!');
            
            // Show welcome message
            if (typeof productManager !== 'undefined') {
                productManager.showAlert('info', 'Welcome! Fill in the product details and use AI assistance if needed.');
            }
        });
    </script>
</body>
</html>
