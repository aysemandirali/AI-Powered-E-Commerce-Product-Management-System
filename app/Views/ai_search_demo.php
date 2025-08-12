<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Destekli Ürün Arama - Demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .demo-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .demo-header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }

        .demo-header h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .demo-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .search-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .search-input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .search-input {
            border: 2px solid #e0e0e0;
            border-radius: 50px;
            padding: 15px 60px 15px 20px;
            font-size: 16px;
            width: 100%;
            outline: none;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50px;
            width: 50px;
            height: 40px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            transform: translateY(-50%) scale(1.05);
        }

        .example-queries {
            margin-top: 20px;
        }

        .example-query {
            display: inline-block;
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 20px;
            padding: 8px 16px;
            margin: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .example-query:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        .results-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            min-height: 400px;
        }

        .ai-analysis {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
        }

        .ai-analysis.ai-fallback {
            background: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%);
        }

        .ai-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }

        .ai-filter {
            background: rgba(255,255,255,0.2);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            border: 1px solid rgba(255,255,255,0.3);
        }

        .product-card {
            border: 1px solid #e0e0e0;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: #667eea;
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .product-brand {
            background: #667eea;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .product-price {
            font-size: 24px;
            font-weight: bold;
            color: #2e7d32;
            margin: 15px 0;
        }

        .product-features {
            color: #666;
            font-style: italic;
            margin: 10px 0;
        }

        .loading-spinner {
            text-align: center;
            padding: 50px;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .no-results {
            text-align: center;
            padding: 50px;
            color: #666;
        }

        .no-results i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="demo-container">
        <!-- Header -->
        <div class="demo-header">
            <h1><i class="fas fa-brain"></i> AI Destekli Ürün Arama</h1>
            <p>Doğal dil ile ürün arayın - Gemini AI sizin için anlam çıkarır</p>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <h3 class="mb-4"><i class="fas fa-search"></i> Akıllı Arama</h3>
            
            <div class="search-input-group">
                <input type="text" id="searchInput" class="search-input" 
                       placeholder="Örnek: 'Apple marka pahalı kulaklık' veya 'kadınlar için kırmızı elbise'...">
                <button class="search-btn" onclick="performSearch()">
                    <i class="fas fa-search"></i>
                </button>
            </div>

            <div class="example-queries">
                <small class="text-muted mb-2 d-block">Örnek aramalar (tıklayarak deneyin):</small>
                <span class="example-query" onclick="setQuery('Apple marka pahalı kulaklık')">Apple marka pahalı kulaklık</span>
                <span class="example-query" onclick="setQuery('kadınlar için kırmızı elbise')">kadınlar için kırmızı elbise</span>
                <span class="example-query" onclick="setQuery('erkek spor ayakkabı')">erkek spor ayakkabı</span>
                <span class="example-query" onclick="setQuery('ucuz telefon')">ucuz telefon</span>
                <span class="example-query" onclick="setQuery('Samsung Galaxy phone')">Samsung Galaxy phone</span>
                <span class="example-query" onclick="setQuery('blue cotton shirt men')">blue cotton shirt men</span>
                <span class="example-query" onclick="setQuery('professional headphones under 500')">professional headphones under 500</span>
                <span class="example-query" onclick="setQuery('luxury leather jacket')">luxury leather jacket</span>
            </div>
        </div>

        <!-- Results Section -->
        <div class="results-section">
            <div id="resultsContainer">
                <div class="text-center text-muted">
                    <i class="fas fa-search fa-3x mb-3" style="opacity: 0.3;"></i>
                    <h5>Arama yapmak için yukarıdaki örneklerden birini tıklayın</h5>
                    <p>veya kendi doğal dil sorgunuzu yazın</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set example query
        function setQuery(query) {
            document.getElementById('searchInput').value = query;
            performSearch();
        }

        // Perform search
        function performSearch() {
            const query = document.getElementById('searchInput').value.trim();
            
            if (!query) {
                alert('Lütfen bir arama terimi girin');
                return;
            }

            showLoading();

            fetch('/ai/search', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `query=${encodeURIComponent(query)}`
            })
            .then(response => response.json())
            .then(data => {
                displayResults(data);
            })
            .catch(error => {
                console.error('Search error:', error);
                showError('Arama sırasında bir hata oluştu');
            });
        }

        // Show loading
        function showLoading() {
            document.getElementById('resultsContainer').innerHTML = `
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <h5>AI sorgunuzu analiz ediyor...</h5>
                    <p class="text-muted">Gemini AI anlam çıkarıyor ve ürünleri eşleştiriyor</p>
                </div>
            `;
        }

        // Display results
        function displayResults(data) {
            const container = document.getElementById('resultsContainer');
            
            if (!data.success) {
                showError('Arama başarısız');
                return;
            }

            let html = '';

            // Show AI analysis
            if (data.gemini_analysis) {
                const analysis = data.gemini_analysis;
                const isAI = analysis.success;
                
                html += `
                    <div class="ai-analysis ${!isAI ? 'ai-fallback' : ''}">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-${isAI ? 'brain' : 'exclamation-triangle'} me-2"></i>
                            <strong>${isAI ? 'AI Analiz Sonucu' : 'Temel Arama'}</strong>
                        </div>
                        
                        <div><strong>Orijinal Sorgu:</strong> "${data.query}"</div>
                        
                        ${isAI && analysis.extracted_filters ? `
                            <div class="ai-filters">
                                ${Object.entries(analysis.extracted_filters)
                                    .filter(([key, value]) => value && value !== '')
                                    .map(([key, value]) => {
                                        if (key === 'keywords' && Array.isArray(value)) {
                                            return `<span class="ai-filter"><strong>${key}:</strong> ${value.join(', ')}</span>`;
                                        }
                                        return `<span class="ai-filter"><strong>${key}:</strong> ${value}</span>`;
                                    }).join('')}
                            </div>
                        ` : ''}
                        
                        ${!isAI ? '<p class="mb-0 mt-2 small">AI analiz kullanılamadı, temel metin araması yapıldı.</p>' : ''}
                    </div>
                `;
            }

            // Show results count
            html += `
                <div class="text-center mb-4">
                    <h4 class="text-primary">${data.products.length} ürün bulundu</h4>
                </div>
            `;

            // Show products
            if (data.products.length > 0) {
                data.products.forEach(product => {
                    html += `
                        <div class="product-card">
                            <div class="product-header">
                                <h5 class="mb-0">${product.title}</h5>
                                <span class="product-brand">${product.brand}</span>
                            </div>
                            <div class="text-muted small mb-2">${product.category_name || 'Kategori'}</div>
                            <p class="mb-2">${product.description}</p>
                            <div class="product-price">$${product.price}</div>
                            <div class="product-features">${product.features}</div>
                        </div>
                    `;
                });
            } else {
                html += `
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h5>Aramanıza uygun ürün bulunamadı</h5>
                        <p>Farklı kelimeler kullanarak tekrar deneyin</p>
                    </div>
                `;
            }

            container.innerHTML = html;
        }

        // Show error
        function showError(message) {
            document.getElementById('resultsContainer').innerHTML = `
                <div class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <h5>${message}</h5>
                </div>
            `;
        }

        // Enter key support
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    </script>
</body>
</html>