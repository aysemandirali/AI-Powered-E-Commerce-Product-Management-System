<!DOCTYPE html>
<html>
<head>
    <title>Quick Test - Product & Search</title>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <style>
        body { font-family: Arial; margin: 20px; }
        .test-section { border: 1px solid #ccc; margin: 10px; padding: 15px; }
        .result { background: #f5f5f5; padding: 10px; margin-top: 10px; max-height: 200px; overflow-y: auto; }
        .success { color: green; }
        .error { color: red; }
        button { padding: 8px 15px; margin: 5px; }
        input, select, textarea { margin: 5px; padding: 5px; }
    </style>
</head>
<body>
    <h1>Quick Test - Product Management & Search</h1>

    <!-- Search Test -->
    <div class="test-section">
        <h3>🔍 Search Test</h3>
        <input type="text" id="searchQuery" placeholder="iPhone, Samsung, cotton, etc." value="iPhone">
        <button onclick="testSearch()">Search Test</button>
        <div id="searchResult" class="result"></div>
    </div>

    <!-- Product Add Test -->
    <div class="test-section">
        <h3>➕ Product Add Test</h3>
        <div>
            <select id="categoryId">
                <option value="1">Electronics</option>
                <option value="2">Clothing</option>
                <option value="3">Home & Garden</option>
            </select>
            <input type="text" id="brand" placeholder="Brand" value="MyBrand">
            <input type="text" id="title" placeholder="Product Title" value="Unique Gaming Laptop">
        </div>
        <div>
            <textarea id="description" placeholder="Description">Test product description with details</textarea>
            <input type="number" id="price" placeholder="Price" value="99.99" step="0.01">
            <input type="number" id="stock" placeholder="Stock" value="10">
        </div>
        <button onclick="testProductAdd()">Add Product Test</button>
        <button onclick="generateRandomProduct()">Generate Random Product</button>
        <div id="productResult" class="result"></div>
    </div>

    <!-- Database Test -->
    <div class="test-section">
        <h3>💾 Database Test</h3>
        <button onclick="testDatabase()">Test Database</button>
        <div id="dbResult" class="result"></div>
    </div>

    <script>
        const baseUrl = 'http://localhost/eticaret-staj/public/';

        function testSearch() {
            const query = document.getElementById('searchQuery').value;
            const resultDiv = document.getElementById('searchResult');

            resultDiv.innerHTML = 'Searching...';

            $.post(baseUrl + 'product/search', {
                query: query,
                limit: 10
            })
            .done(function(response) {
                console.log('Search Response:', response);

                if (response.success) {
                    let html = `<div class="success">✅ Search Success!</div>`;
                    html += `<p>Query: "${response.query}" | Found: ${response.total_found} products</p>`;
                    html += `<p>Method: ${response.search_method || 'basic'}</p>`;

                    if (response.products && response.products.length > 0) {
                        html += '<ul>';
                        response.products.forEach(product => {
                            html += `<li><strong>${product.brand}</strong> - ${product.title} ($${product.price})</li>`;
                        });
                        html += '</ul>';
                    } else {
                        html += '<p style="color:orange">No products found</p>';
                    }
                } else {
                    html = `<div class="error">❌ Search Failed: ${response.message}</div>`;
                }

                resultDiv.innerHTML = html;
            })
            .fail(function(xhr, status, error) {
                resultDiv.innerHTML = `<div class="error">❌ AJAX Error: ${error}</div>`;
                console.error('Search failed:', xhr.responseText);
            });
        }

        function testProductAdd() {
            const productData = {
                category_id: document.getElementById('categoryId').value,
                brand: document.getElementById('brand').value,
                title: document.getElementById('title').value,
                description: document.getElementById('description').value,
                price: document.getElementById('price').value,
                stock: document.getElementById('stock').value
            };

            const resultDiv = document.getElementById('productResult');
            resultDiv.innerHTML = 'Adding product...';

            $.post(baseUrl + 'product/save', productData)
            .done(function(response) {
                console.log('Product Add Response:', response);

                if (response.success) {
                    const html = `<div class="success">✅ Product Added Successfully!</div>
                                 <p>Product ID: ${response.product_id}</p>
                                 <p>Action: ${response.action}</p>
                                 <p>Message: ${response.message}</p>
                                 <p>Generated Slug: ${response.slug || 'N/A'}</p>`;
                    resultDiv.innerHTML = html;
                } else {
                    let html = `<div class="error">❌ Product Add Failed: ${response.message}</div>`;

                    if (response.error_details) {
                        html += `<p><strong>Error Details:</strong> ${response.error_details}</p>`;
                    }

                    if (response.error_type) {
                        html += `<p><strong>Error Type:</strong> ${response.error_type}</p>`;
                    }

                    if (response.generated_slug) {
                        html += `<p><strong>Generated Slug:</strong> ${response.generated_slug}</p>`;
                    }

                    if (response.attempted_data) {
                        html += `<p><strong>Attempted Data:</strong></p><pre>${JSON.stringify(response.attempted_data, null, 2)}</pre>`;
                    }

                    if (response.errors) {
                        html += '<p><strong>Validation Errors:</strong></p><ul>';
                        Object.keys(response.errors).forEach(field => {
                            html += `<li>${field}: ${response.errors[field]}</li>`;
                        });
                        html += '</ul>';
                    }

                    resultDiv.innerHTML = html;
                }
            })
            .fail(function(xhr, status, error) {
                resultDiv.innerHTML = `<div class="error">❌ AJAX Error: ${error}</div>`;
                console.error('Product add failed:', xhr.responseText);
            });
        }

        function testDatabase() {
            const resultDiv = document.getElementById('dbResult');
            resultDiv.innerHTML = 'Testing database...';

            // Test using cart count endpoint (simple database test)
            $.get(baseUrl + 'product/getCartCount')
            .done(function(response) {
                console.log('DB Test Response:', response);

                if (response.success !== undefined) {
                    resultDiv.innerHTML = `<div class="success">✅ Database Connection Working!</div>
                                         <p>Cart Count: ${response.count}</p>`;
                } else {
                    resultDiv.innerHTML = `<div class="error">❌ Unexpected response format</div>`;
                }
            })
            .fail(function(xhr, status, error) {
                resultDiv.innerHTML = `<div class="error">❌ Database connection failed: ${error}</div>`;
                console.error('DB test failed:', xhr.responseText);
            });
        }

        function generateRandomProduct() {
            const brands = ['Apple', 'Samsung', 'Dell', 'HP', 'Asus', 'MSI', 'Razer', 'Alienware'];
            const products = ['Gaming Laptop', 'Wireless Mouse', 'Mechanical Keyboard', 'Monitor', 'Tablet', 'Smartphone', 'Headphones', 'Webcam'];
            const adjectives = ['Pro', 'Ultra', 'Elite', 'Premium', 'Advanced', 'Professional', 'Gaming', 'Business'];

            const randomBrand = brands[Math.floor(Math.random() * brands.length)];
            const randomProduct = products[Math.floor(Math.random() * products.length)];
            const randomAdjective = adjectives[Math.floor(Math.random() * adjectives.length)];
            const randomNumber = Math.floor(Math.random() * 1000) + 1;

            const title = `${randomBrand} ${randomAdjective} ${randomProduct} ${randomNumber}`;
            const price = (Math.random() * 2000 + 50).toFixed(2);
            const stock = Math.floor(Math.random() * 100) + 1;

            document.getElementById('brand').value = randomBrand;
            document.getElementById('title').value = title;
            document.getElementById('price').value = price;
            document.getElementById('stock').value = stock;
            document.getElementById('description').value = `High-quality ${randomProduct.toLowerCase()} from ${randomBrand}. Perfect for gaming and professional use.`;
        }

        // Auto-run basic tests on page load
        $(document).ready(function() {
            console.log('Quick Test Page Loaded');
            testDatabase();
        });
    </script>
</body>
</html>
