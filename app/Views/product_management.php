<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .main-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .content-wrapper {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin: 20px;
            overflow: hidden;
        }

        .sidebar-panel {
            background: #f8f9fa;
            border-left: 1px solid #dee2e6;
            min-height: 600px;
            transition: all 0.3s ease;
            position: relative;
        }

        .sidebar-panel.collapsed {
            display: none;
        }

        .form-section {
            padding: 30px;
        }

        .validation-badge {
            display: none;
            margin-top: 5px;
        }

        .validation-badge.success {
            color: #198754;
        }

        .validation-badge.warning {
            color: #fd7e14;
        }

        .validation-badge.error {
            color: #dc3545;
        }

        .product-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }

        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 1px solid #dee2e6;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .quantity-btn:hover {
            background: #e9ecef;
        }

        .quantity-input {
            width: 50px;
            text-align: center;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 5px;
        }

        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ai-panel-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }

        .search-suggestions {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }

        .loading-spinner {
            display: none;
        }

        .mode-indicator {
            background: #e3f2fd;
            color: #1976d2;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #1976d2;
        }

        .field-group {
            position: relative;
            margin-bottom: 20px;
        }

        .validate-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
        }

        .field-group textarea + .validate-btn {
            top: 40px;
        }

        @media (max-width: 768px) {
            .content-wrapper {
                margin: 10px;
            }

            .form-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="container-fluid">
            <div class="content-wrapper">
                <div class="row g-0">
                    <!-- Left Panel - Product Form -->
                    <div class="col-lg-12" id="main-panel">
                        <div class="form-section">
                            <!-- Mode Indicator -->
                            <div class="mode-indicator" id="mode-indicator" style="display: none;">
                                <i class="fas fa-edit me-2"></i>
                                <span id="mode-text">EDITING MODE</span>
                            </div>

                            <!-- Header -->
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h2 class="mb-0">
                                    <i class="fas fa-box-open text-primary me-2"></i>
                                    Product Management
                                </h2>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-primary" id="toggle-ai-panel">
                                        <i class="fas fa-robot me-1"></i>
                                        AI & Results
                                    </button>
                                    <div class="position-relative">
                                        <button type="button" class="btn btn-outline-secondary" id="cart-btn">
                                            <i class="fas fa-shopping-cart me-1"></i>
                                            Cart
                                        </button>
                                        <span class="cart-badge" id="cart-badge" style="display: none;">0</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Product Form -->
                            <form id="product-form">
                                <input type="hidden" id="product-id" name="id">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="field-group">
                                            <label for="category_id" class="form-label">Category *</label>
                                            <select class="form-select" id="category_id" name="category_id" required>
                                                <option value="">Select Category</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?= $category['id'] ?>"><?= esc($category['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="field-group">
                                            <label for="brand" class="form-label">Brand *</label>
                                            <input type="text" class="form-control" id="brand" name="brand" required>
                                            <button type="button" class="btn btn-sm btn-outline-primary validate-btn" data-field="brand">
                                                Validate
                                            </button>
                                            <div class="validation-badge" id="brand-validation"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="field-group">
                                    <label for="title" class="form-label">Product Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                    <button type="button" class="btn btn-sm btn-outline-primary validate-btn" data-field="title">
                                        Validate
                                    </button>
                                    <div class="validation-badge" id="title-validation"></div>
                                </div>

                                <div class="field-group">
                                    <label for="description" class="form-label">Description *</label>
                                    <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                                    <button type="button" class="btn btn-sm btn-outline-primary validate-btn" data-field="description">
                                        Validate
                                    </button>
                                    <div class="validation-badge" id="description-validation"></div>
                                </div>

                                <div class="field-group">
                                    <label for="meta_seo" class="form-label">SEO Meta Description</label>
                                    <textarea class="form-control" id="meta_seo" name="meta_seo" rows="2" placeholder="150-160 characters for optimal SEO"></textarea>
                                    <button type="button" class="btn btn-sm btn-outline-primary validate-btn" data-field="meta_seo">
                                        Validate
                                    </button>
                                    <div class="validation-badge" id="meta_seo-validation"></div>
                                </div>

                                <div class="field-group">
                                    <label for="features" class="form-label">Features</label>
                                    <textarea class="form-control" id="features" name="features" rows="3" placeholder="Key product features and specifications"></textarea>
                                    <button type="button" class="btn btn-sm btn-outline-primary validate-btn" data-field="features">
                                        Validate
                                    </button>
                                    <div class="validation-badge" id="features-validation"></div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="field-group">
                                            <label for="price" class="form-label">Price ($) *</label>
                                            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                                            <button type="button" class="btn btn-sm btn-outline-primary validate-btn" data-field="price">
                                                Validate
                                            </button>
                                            <div class="validation-badge" id="price-validation"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="stock" class="form-label">Stock Quantity *</label>
                                            <input type="number" class="form-control" id="stock" name="stock" min="0" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-flex gap-3 mt-4">
                                    <button type="button" class="btn btn-success" id="save-product">
                                        <i class="fas fa-save me-1"></i>
                                        <span id="save-btn-text">Save Product</span>
                                    </button>
                                    <button type="button" class="btn btn-primary" id="new-product">
                                        <i class="fas fa-plus me-1"></i>
                                        New Product
                                    </button>

                                    <button type="button" class="btn btn-secondary" id="clear-form">
                                        <i class="fas fa-broom me-1"></i>
                                        Clear
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Right Panel - AI & Results -->
                    <div class="col-lg-4 sidebar-panel collapsed" id="ai-panel" style="display: none;">
                        <div class="ai-panel-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-robot me-2"></i>
                                    AI & Search Results
                                </h5>
                                <button type="button" class="btn btn-sm btn-light" id="close-ai-panel">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <div class="p-3">
                            <!-- AI Search Section -->
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3">
                                    <i class="fas fa-search text-primary me-1"></i>
                                    AI Product Search
                                </h6>
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" id="ai-search-input" placeholder="e.g., red cotton t-shirt under $50">
                                    <button class="btn btn-primary" type="button" id="ai-search-btn">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                                <div class="search-suggestions">
                                    Try: "iPhone under $800", "women's red dress", "gaming laptop"
                                </div>
                                <div class="text-center loading-spinner" id="search-loading">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Searching...</span>
                                    </div>
                                    <div class="mt-1 small">AI is analyzing...</div>
                                </div>
                            </div>

                            <!-- Search Results -->
                            <div id="search-results">
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-search fa-2x mb-2"></i>
                                    <p>Search for products using natural language</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cart Modal -->
    <div class="modal fade" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cartModalLabel">
                        <i class="fas fa-shopping-cart me-2"></i>Shopping Cart
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="cart-loading" class="text-center py-4" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading cart...</span>
                        </div>
                        <div class="mt-2">Loading your cart...</div>
                    </div>

                    <div id="cart-empty" class="text-center py-5" style="display: none;">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Your cart is empty</h6>
                        <p class="text-muted">Add some products to see them here</p>
                    </div>

                    <div id="cart-items">
                        <!-- Cart items will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="w-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <strong>Total: <span id="cart-total">$0.00</span></strong>
                            <span class="text-muted"><span id="cart-item-count">0</span> items</span>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-arrow-left me-1"></i>Continue Shopping
                            </button>
                            <button type="button" class="btn btn-success flex-fill" id="checkout-btn">
                                <i class="fas fa-credit-card me-1"></i>Proceed to Checkout
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize
            let currentEditingId = null;
            let aiPanelVisible = false;

            // Update cart count on load
            updateCartCount();

            // Toggle AI Panel
            $('#toggle-ai-panel, #close-ai-panel').on('click', function() {
                aiPanelVisible = !aiPanelVisible;
                if (aiPanelVisible) {
                    $('#ai-panel').removeClass('collapsed').show();
                    $('#main-panel').removeClass('col-lg-12').addClass('col-lg-8');
                } else {
                    $('#ai-panel').addClass('collapsed').hide();
                    $('#main-panel').removeClass('col-lg-8').addClass('col-lg-12');
                }
            });

            // Field Validation
            $('.validate-btn').on('click', function() {
                const fieldName = $(this).data('field');
                const content = $('#' + fieldName).val().trim();
                const category = $('#category_id option:selected').text();

                if (!content) {
                    showAlert('warning', 'Please enter content to validate');
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.html('<span class="spinner-border spinner-border-sm"></span>');

                $.post('<?= base_url('product/validateField') ?>', {
                    field: fieldName,
                    content: content,
                    category: category
                })
                .done(function(response) {
                    showFieldValidation(fieldName, response);
                })
                .fail(function() {
                    showAlert('error', 'Validation service temporarily unavailable');
                })
                .always(function() {
                    btn.html(originalText);
                });
            });

            // AI Search
            $('#ai-search-btn').on('click', function() {
                performAISearch();
            });

            $('#ai-search-input').on('keypress', function(e) {
                if (e.which === 13) {
                    performAISearch();
                }
            });

            // Save Product
            $('#save-product').on('click', function() {
                saveProduct();
            });

            // New Product
            $('#new-product').on('click', function() {
                clearForm();
                setEditMode(false);
            });

            // Clear Form
            $('#clear-form').on('click', function() {
                if (confirm('Are you sure you want to clear all fields?')) {
                    clearForm();
                }
            });



            // Cart Modal
            $('#cart-btn').on('click', function() {
                loadCartContents();
            });

            // Functions
            function performAISearch() {
                const query = $('#ai-search-input').val().trim();
                if (!query) {
                    showAlert('warning', 'Please enter a search query');
                    return;
                }

                $('#search-loading').show();
                $('#search-results').empty();

                $.post('<?= base_url('product/search') ?>', {
                    query: query,
                    limit: 20
                })
                .done(function(response) {
                    displaySearchResults(response);
                })
                .fail(function() {
                    $('#search-results').html('<div class="alert alert-danger">Search failed. Please try again.</div>');
                })
                .always(function() {
                    $('#search-loading').hide();
                });
            }

            function displaySearchResults(response) {
                if (!response.success || !response.products || response.products.length === 0) {
                    $('#search-results').html(`
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-search-minus fa-2x mb-2"></i>
                            <p>No products found for "${response.query}"</p>
                            <small>Try different keywords or broader terms</small>
                        </div>
                    `);
                    return;
                }

                let html = `<div class="mb-2"><small class="text-muted">Found ${response.total_found} products</small></div>`;

                response.products.forEach(function(product) {
                    html += `
                        <div class="product-card mb-3 p-3" data-product-id="${product.id}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 text-primary">${product.brand}</h6>
                                    <p class="mb-1 fw-bold">${product.title}</p>
                                    <p class="mb-2 text-muted small">${product.description.substring(0, 100)}...</p>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="h6 text-success">$${product.price}</span>
                                    <br><small class="text-muted">Stock: ${product.stock}</small>
                                </div>
                                <div class="quantity-controls">
                                    <div class="quantity-btn" onclick="changeQuantity(${product.id}, -1)">-</div>
                                    <input type="number" class="quantity-input" id="qty-${product.id}" value="1" min="1" max="${product.stock}">
                                    <div class="quantity-btn" onclick="changeQuantity(${product.id}, 1)">+</div>
                                    <button class="btn btn-sm btn-primary ms-2" onclick="addToCart(${product.id})">
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });

                $('#search-results').html(html);

                // Make product cards clickable to edit
                $('.product-card').on('click', function(e) {
                    if (!$(e.target).closest('.quantity-controls, .btn').length) {
                        const productId = $(this).data('product-id');
                        loadProductForEdit(productId);
                    }
                });
            }

            function saveProduct() {
                const formData = $('#product-form').serialize();
                const btn = $('#save-product');
                const originalText = btn.html();

                btn.html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

                $.post('<?= base_url('product/save') ?>', formData)
                .done(function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        if (response.action === 'created') {
                            setEditMode(true, response.product_id);
                        }
                    } else {
                        showAlert('error', response.message);
                        if (response.errors) {
                            Object.keys(response.errors).forEach(function(field) {
                                showFieldError(field, response.errors[field]);
                            });
                        }
                    }
                })
                .fail(function() {
                    showAlert('error', 'Failed to save product');
                })
                .always(function() {
                    btn.html(originalText);
                });
            }

            function loadProductForEdit(productId) {
                $.get('<?= base_url('product/getProduct') ?>/' + productId)
                .done(function(response) {
                    if (response.success) {
                        populateForm(response.product);
                        setEditMode(true, productId);
                        showAlert('info', 'Product loaded for editing');
                    } else {
                        showAlert('error', 'Failed to load product');
                    }
                })
                .fail(function() {
                    showAlert('error', 'Failed to load product');
                });
            }

            function populateForm(product) {
                $('#product-id').val(product.id);
                $('#category_id').val(product.category_id);
                $('#brand').val(product.brand);
                $('#title').val(product.title);
                $('#description').val(product.description);
                $('#meta_seo').val(product.meta_seo || '');
                $('#features').val(product.features || '');
                $('#price').val(product.price);
                $('#stock').val(product.stock);
            }

            function setEditMode(editing, productId = null) {
                currentEditingId = editing ? productId : null;
                if (editing) {
                    $('#mode-indicator').show();
                    $('#mode-text').text(`EDITING MODE - Product #${productId}`);
                    $('#save-btn-text').text('Update Product');
                } else {
                    $('#mode-indicator').hide();
                    $('#save-btn-text').text('Save Product');
                }
            }

            function clearForm() {
                $('#product-form')[0].reset();
                $('#product-id').val('');
                setEditMode(false);
                $('.validation-badge').hide();
            }

            function showFieldValidation(fieldName, response) {
                const badge = $('#' + fieldName + '-validation');
                badge.show();

                if (response.success) {
                    badge.removeClass('warning error').addClass('success');
                    badge.html('<i class="fas fa-check-circle me-1"></i>' + response.analysis);
                } else {
                    badge.removeClass('success warning').addClass('error');
                    badge.html('<i class="fas fa-exclamation-circle me-1"></i>' + response.message);
                }
            }

            function showFieldError(field, message) {
                const element = $('#' + field);
                element.addClass('is-invalid');

                setTimeout(function() {
                    element.removeClass('is-invalid');
                }, 3000);
            }



            function updateCartCount() {
                $.get('<?= base_url('product/getCartCount') ?>')
                .done(function(response) {
                    if (response.success && response.count > 0) {
                        $('#cart-badge').text(response.count).show();
                    } else {
                        $('#cart-badge').hide();
                    }
                });
            }

            function loadCartContents() {
                $('#cart-loading').show();
                $('#cart-empty').hide();
                $('#cart-items').hide();
                $('#cartModal').modal('show');

                // Use the global function
                loadCartContentsOnly();
                $('#cart-loading').hide();
            }

            // Use global displayCartItems function

        });

        // Global functions for cart operations
        function changeQuantity(productId, change) {
            const input = $('#qty-' + productId);
            const currentQty = parseInt(input.val()) || 1;
            const newQty = Math.max(1, Math.min(parseInt(input.attr('max')), currentQty + change));
            input.val(newQty);
        }

        function addToCart(productId) {
            const quantity = parseInt($('#qty-' + productId).val()) || 1;

            $.post('<?= base_url('product/addToCart') ?>', {
                product_id: productId,
                quantity: quantity
            })
            .done(function(response) {
                if (response.success) {
                    if (response.cart_count > 0) {
                        $('#cart-badge').text(response.cart_count).show();
                    }

                    // Show success animation
                    const btn = $(`button[onclick="addToCart(${productId})"]`);
                    const originalHtml = btn.html();
                    btn.html('<i class="fas fa-check text-success"></i>').prop('disabled', true);

                    setTimeout(function() {
                        btn.html(originalHtml).prop('disabled', false);
                    }, 1500);

                } else {
                    alert(response.message);
                }
            })
            .fail(function() {
                alert('Failed to add item to cart');
            });
        }

        // Global functions for cart modal operations
        function updateCartItemQuantity(productId, newQuantity) {
            // Convert to integer to avoid string concatenation
            newQuantity = parseInt(newQuantity);

            if (isNaN(newQuantity) || newQuantity < 1) {
                if (confirm('Remove this item from cart?')) {
                    removeCartItem(productId);
                }
                return;
            }

            // Find current item to check stock
            const cartItem = $(`.cart-item[data-product-id="${productId}"]`);
            const stockText = cartItem.find('small:contains("Stock:")').text();
            const maxStock = parseInt(stockText.replace('Stock: ', ''));

            if (newQuantity > maxStock) {
                showAlert('warning', `Maximum ${maxStock} items available in stock`);
                // Reset to max stock
                cartItem.find('input[type="number"]').val(maxStock);
                newQuantity = maxStock;
            }

            $.post('<?= base_url('product/updateCartItem') ?>', {
                product_id: productId,
                quantity: newQuantity
            })
            .done(function(response) {
                if (response.success) {
                    // Reload cart contents
                    loadCartContentsOnly();
                    // Update header badge
                    updateCartCount();
                    showAlert('success', response.message);
                } else {
                    showAlert('error', response.message);
                    loadCartContentsOnly();
                }
            })
            .fail(function() {
                showAlert('error', 'Failed to update cart');
                loadCartContentsOnly();
            });
        }

        function removeCartItem(productId) {
            $.post('<?= base_url('product/removeFromCart') ?>', {
                product_id: productId
            })
            .done(function(response) {
                if (response.success) {
                    loadCartContentsOnly();
                    updateCartCount();
                    showAlert('success', 'Item removed from cart');
                } else {
                    showAlert('error', response.message);
                }
            })
            .fail(function() {
                showAlert('error', 'Failed to remove item');
            });
        }

        // Load cart contents without showing modal (for updates)
        function loadCartContentsOnly() {
            $.get('<?= base_url('product/getCartContents') ?>')
            .done(function(response) {
                if (response.success) {
                    if (response.total_items > 0) {
                        displayCartItems(response.cart.items);
                        $('#cart-total').text('$' + parseFloat(response.total_amount).toFixed(2));
                        $('#cart-item-count').text(response.total_items);
                        $('#cart-items').show();
                        $('#cart-empty').hide();
                        $('#checkout-btn').prop('disabled', false);
                    } else {
                        $('#cart-empty').show();
                        $('#cart-items').hide();
                        $('#cart-total').text('$0.00');
                        $('#cart-item-count').text('0');
                        $('#checkout-btn').prop('disabled', true);
                    }
                }
            });
        }

        // Global function to display cart items
        function displayCartItems(items) {
            let html = '';

            items.forEach(function(item) {
                const subtotal = (item.quantity * item.price).toFixed(2);

                html += `
                    <div class="cart-item border-bottom pb-3 mb-3" data-product-id="${item.product_id}">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h6 class="mb-1">${item.title}</h6>
                                <small class="text-muted">${item.brand}</small>
                                <br><small class="text-success">$${parseFloat(item.price).toFixed(2)} each</small>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group input-group-sm">
                                    <button class="btn btn-outline-secondary" onclick="updateCartItemQuantity(${item.product_id}, ${parseInt(item.quantity) - 1})">-</button>
                                    <input type="number" class="form-control text-center" value="${item.quantity}" min="1" max="${item.stock}"
                                           onchange="updateCartItemQuantity(${item.product_id}, parseInt(this.value))">
                                    <button class="btn btn-outline-secondary" onclick="updateCartItemQuantity(${item.product_id}, ${parseInt(item.quantity) + 1})">+</button>
                                </div>
                                <small class="text-muted">Stock: ${item.stock}</small>
                            </div>
                            <div class="col-md-2 text-end">
                                <strong>$${subtotal}</strong>
                            </div>
                            <div class="col-md-1 text-end">
                                <button class="btn btn-sm btn-outline-danger" onclick="removeCartItem(${item.product_id})" title="Remove">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });

            $('#cart-items').html(html);
        }

        // Global alert function
        function showAlert(type, message) {
            const alertClass = type === 'success' ? 'alert-success' :
                             type === 'error' ? 'alert-danger' :
                             type === 'warning' ? 'alert-warning' : 'alert-info';

            const alert = $(`
                <div class="alert ${alertClass} alert-dismissible fade show position-fixed"
                     style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);

            $('body').append(alert);

            setTimeout(function() {
                alert.alert('close');
            }, 5000);
        }

        // Global function to update cart count
        function updateCartCount() {
            $.get('<?= base_url('product/getCartCount') ?>')
            .done(function(response) {
                if (response.success && response.count > 0) {
                    $('#cart-badge').text(response.count).show();
                } else {
                    $('#cart-badge').hide();
                }
            });
        }
    </script>
</body>
</html>
