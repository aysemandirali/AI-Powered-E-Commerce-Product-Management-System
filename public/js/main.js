// Global değişkenler
let currentProductId = null;
let products = [
  {
    id: 1,
    title: "Premium Kırmızı T-Shirt",
    category: "giyim",
    description: "Yüksek kalite %100 pamuk, nefes alabilen kumaş yapısı",
    metaSeo:
      "Premium kırmızı tişört, %100 pamuk, rahat kesim, online alışveriş",
    features:
      "Antibakteriyel özellik, Fade-resistant teknoloji, 4 mevsim kullanım",
  },
  {
    id: 2,
    title: "Kablosuz Bluetooth Kulaklık",
    category: "elektronik",
    description: "40mm sürücü, 30 saat pil ömrü, aktif gürültü engelleme",
    metaSeo: "Bluetooth kulaklık, kablosuz, noise cancelling, uzun pil ömrü",
    features: "ANC teknolojisi, Type-C şarj, Çoklu cihaz bağlantısı",
  },
];

// Sayfa yüklendiğinde
document.addEventListener("DOMContentLoaded", function () {
  initializeEventListeners();
});

// Event listener'ları başlat
function initializeEventListeners() {
  // AI Panel toggle
  const aiToggleBtn = document.getElementById("aiToggleBtn");
  if (aiToggleBtn) {
    aiToggleBtn.addEventListener("click", toggleAIPanel);
  }

  // AI Panel kapat butonu
  const closeAIBtn = document.querySelector(".close-ai-btn");
  if (closeAIBtn) {
    closeAIBtn.addEventListener("click", () => {
      document.getElementById("aiPanel").classList.remove("active");
    });
  }

  // Form elemanlarına focus olduğunda stil değişikliği
  const formInputs = document.querySelectorAll(
    ".form-input, .form-textarea, .form-select"
  );
  formInputs.forEach((input) => {
    input.addEventListener("focus", () => {
      input.parentElement.style.transform = "scale(1.02)";
    });

    input.addEventListener("blur", () => {
      input.parentElement.style.transform = "scale(1)";
    });
  });
}

// AI Panel Aç/Kapa
function toggleAIPanel() {
  const panel = document.getElementById("aiPanel");
  panel.classList.toggle("active");

  if (panel.classList.contains("active")) {
    document.getElementById("aiPrompt").focus();
  }
}

// AI ile Arama - Enhanced with Real Gemini Integration
function searchWithAI() {
  const prompt = document.getElementById("aiPrompt").value.trim();

  if (!prompt) {
    showNotification("Lütfen bir arama terimi girin", "warning");
    return;
  }

  // Loading göster
  showLoading();

  // Show what AI is thinking
  showAIThinking(prompt);

  // AJAX call to Gemini-powered search
  fetch("/ai/search", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `query=${encodeURIComponent(prompt)}`,
  })
    .then((response) => response.json())
    .then((data) => {
      hideLoading();

      if (data.success) {
        displayResults(data.products, data.gemini_analysis);

        if (data.products.length === 0) {
          showNotification("Aramanıza uygun ürün bulunamadı", "info");
        } else {
          const aiUsed = data.gemini_analysis.success
            ? " (AI destekli)"
            : " (Temel arama)";
          showNotification(
            `${data.products.length} ürün bulundu${aiUsed}`,
            "success"
          );
        }
      } else {
        showNotification("Arama sırasında bir hata oluştu", "error");
      }
    })
    .catch((error) => {
      hideLoading();
      console.error("Search error:", error);
      showNotification("Arama sırasında bir hata oluştu", "error");
    });
}

// Ürün arama fonksiyonu
function searchProducts(query) {
  const searchTerm = query.toLowerCase();
  return products.filter(
    (product) =>
      product.title.toLowerCase().includes(searchTerm) ||
      product.description.toLowerCase().includes(searchTerm) ||
      product.category.toLowerCase().includes(searchTerm)
  );
}

// Sonuçları göster - Enhanced with AI Analysis Display
function displayResults(results, aiAnalysis = null) {
  const resultsPanel = document.getElementById("resultsPanel");
  const resultsContainer = document.getElementById("resultsContainer");

  resultsPanel.classList.add("active");

  // Show AI analysis if available
  let analysisHtml = "";
  if (aiAnalysis && aiAnalysis.success) {
    const filters = aiAnalysis.extracted_filters || {};
    const filterList = Object.entries(filters)
      .filter(([key, value]) => value && value !== "")
      .map(([key, value]) => {
        if (key === "keywords" && Array.isArray(value)) {
          return `<span class="ai-filter"><strong>${key}:</strong> ${value.join(
            ", "
          )}</span>`;
        }
        return `<span class="ai-filter"><strong>${key}:</strong> ${value}</span>`;
      });

    if (filterList.length > 0) {
      analysisHtml = `
                <div class="ai-analysis">
                    <div class="ai-analysis-header">
                        <i class="fas fa-brain text-primary"></i>
                        <strong>AI Analiz:</strong>
                    </div>
                    <div class="ai-filters">
                        ${filterList.join("")}
                    </div>
                </div>
            `;
    }
  } else if (aiAnalysis && !aiAnalysis.success) {
    analysisHtml = `
            <div class="ai-analysis ai-fallback">
                <div class="ai-analysis-header">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                    <strong>Temel Arama:</strong>
                </div>
                <p class="small text-muted">AI analiz kullanılamadı, temel metin araması yapıldı.</p>
            </div>
        `;
  }

  if (results.length === 0) {
    resultsContainer.innerHTML = `
            ${analysisHtml}
            <div class="no-results">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <p class="text-muted">Aramanıza uygun ürün bulunamadı</p>
                <p class="small text-muted">Farklı kelimeler kullanarak tekrar deneyin</p>
            </div>
        `;
    return;
  }

  resultsContainer.innerHTML = `
        ${analysisHtml}
        <div class="results-count">
            <strong>${results.length}</strong> ürün bulundu
        </div>
        ${results
          .map(
            (product) => `
            <div class="product-card" onclick="loadProduct(${product.id})">
                <div class="product-header">
                    <h4 class="product-title">${product.title}</h4>
                    <span class="product-brand">${product.brand}</span>
                </div>
                <span class="product-category">${
                  product.category_name || getCategoryName(product.category)
                }</span>
                <p class="product-description">${product.description}</p>
                <div class="product-price">$${product.price}</div>
                <div class="product-features">
                    <small class="text-muted">${product.features}</small>
                </div>
                <div class="product-actions">
                    <button class="btn-edit" onclick="editProduct(${
                      product.id
                    }, event)">
                        <i class="fas fa-edit"></i> Düzenle
                    </button>
                    <button class="btn-delete" onclick="deleteProduct(${
                      product.id
                    }, event)">
                        <i class="fas fa-trash"></i> Sil
                    </button>
                </div>
            </div>
        `
          )
          .join("")}
    `;
}

// Kategori adını getir
function getCategoryName(category) {
  const categories = {
    giyim: "Giyim",
    elektronik: "Elektronik",
    "ev-yasam": "Ev & Yaşam",
    spor: "Spor",
  };
  return categories[category] || category;
}

// AI Sıfırla
function resetAI() {
  document.getElementById("aiPrompt").value = "";
  document.getElementById("resultsPanel").classList.remove("active");
  showNotification("AI araması sıfırlandı", "info");
}

// Alan doğrulama
function validateField(fieldId) {
  const field = document.getElementById(fieldId);
  const value = field.value.trim();
  const btn = field.parentElement.querySelector(".validate-btn");

  // Basit doğrulama kuralları
  let isValid = false;
  let message = "";

  switch (fieldId) {
    case "productTitle":
      isValid = value.length >= 5 && value.length <= 100;
      message = isValid
        ? "Başlık uygun"
        : "Başlık 5-100 karakter arasında olmalı";
      break;

    case "metaSeo":
      isValid = value.length >= 50 && value.length <= 160;
      message = isValid
        ? "SEO açıklaması uygun"
        : "SEO açıklaması 50-160 karakter arasında olmalı";
      break;

    case "productDescription":
      isValid = value.length >= 20;
      message = isValid
        ? "Açıklama uygun"
        : "Açıklama en az 20 karakter olmalı";
      break;

    case "productFeatures":
      const features = value.split(",").filter((f) => f.trim());
      isValid = features.length >= 1;
      message = isValid ? "Özellikler uygun" : "En az 1 özellik girmelisiniz";
      break;
  }

  // Buton durumunu güncelle
  if (isValid) {
    btn.classList.remove("invalid");
    btn.classList.add("valid");
    btn.innerHTML = '<i class="fas fa-check-circle"></i> Doğrulandı';
  } else {
    btn.classList.remove("valid");
    btn.classList.add("invalid");
    btn.innerHTML = '<i class="fas fa-times-circle"></i> Geçersiz';
  }

  // Bildirim göster
  showNotification(message, isValid ? "success" : "error");

  return isValid;
}

// Tüm alanları doğrula
function validateAllFields() {
  const fields = [
    "productTitle",
    "metaSeo",
    "productDescription",
    "productFeatures",
  ];
  let allValid = true;

  fields.forEach((fieldId) => {
    if (!validateField(fieldId)) {
      allValid = false;
    }
  });

  const category = document.getElementById("productCategory").value;
  if (!category) {
    allValid = false;
    showNotification("Lütfen bir kategori seçin", "error");
  }

  return allValid;
}

// Ürün kaydet
function saveProduct() {
  if (!validateAllFields()) {
    showNotification("Lütfen tüm alanları doğru şekilde doldurun", "error");
    return;
  }

  const productData = {
    title: document.getElementById("productTitle").value,
    metaSeo: document.getElementById("metaSeo").value,
    description: document.getElementById("productDescription").value,
    features: document.getElementById("productFeatures").value,
    category: document.getElementById("productCategory").value,
  };

  showLoading();

  // Simüle edilmiş kaydetme (gerçek projede AJAX kullanılacak)
  setTimeout(() => {
    if (currentProductId) {
      // Güncelleme
      const index = products.findIndex((p) => p.id === currentProductId);
      products[index] = { ...productData, id: currentProductId };
      showNotification("Ürün başarıyla güncellendi!", "success");
    } else {
      // Yeni ürün
      const newProduct = {
        ...productData,
        id: products.length + 1,
      };
      products.push(newProduct);
      showNotification("Ürün başarıyla eklendi!", "success");
    }

    hideLoading();
    resetForm();
  }, 1500);
}

// Formu sıfırla
function resetForm() {
  document.getElementById("productForm").reset();
  currentProductId = null;

  // Doğrulama butonlarını sıfırla
  document.querySelectorAll(".validate-btn").forEach((btn) => {
    btn.classList.remove("valid", "invalid");
    btn.innerHTML = '<i class="fas fa-check"></i> Doğrula';
  });
}

// Yeni ürün ekle
function newProduct() {
  resetForm();
  showNotification("Yeni ürün ekleme modu aktif", "info");
}

// Ürün yükle
function loadProduct(productId) {
  const product = products.find((p) => p.id === productId);
  if (!product) return;

  currentProductId = productId;

  // Form alanlarını doldur
  document.getElementById("productTitle").value = product.title;
  document.getElementById("metaSeo").value = product.metaSeo;
  document.getElementById("productDescription").value = product.description;
  document.getElementById("productFeatures").value = product.features;
  document.getElementById("productCategory").value = product.category;

  // Scroll to form
  document.querySelector(".form-panel").scrollIntoView({ behavior: "smooth" });

  showNotification("Ürün düzenleme moduna geçildi", "info");
}

// Ürün düzenle
function editProduct(productId, event) {
  event.stopPropagation();
  loadProduct(productId);
}

// Ürün sil
function deleteProduct(productId, event) {
  event.stopPropagation();

  if (confirm("Bu ürünü silmek istediğinizden emin misiniz?")) {
    products = products.filter((p) => p.id !== productId);

    // Sonuçları yeniden göster
    const prompt = document.getElementById("aiPrompt").value.trim();
    if (prompt) {
      const results = searchProducts(prompt);
      displayResults(results);
    }

    showNotification("Ürün başarıyla silindi", "success");
  }
}

// Loading göster/gizle
function showLoading() {
  // Loading overlay ekle
  const loading = document.createElement("div");
  loading.id = "loadingOverlay";
  loading.innerHTML = `
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Yükleniyor...</span>
        </div>
    `;
  loading.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    `;
  document.body.appendChild(loading);
}

function hideLoading() {
  const loading = document.getElementById("loadingOverlay");
  if (loading) {
    loading.remove();
  }
}

// Bildirim göster
function showNotification(message, type = "info") {
  // Mevcut bildirimi kaldır
  const existingNotification = document.querySelector(".notification");
  if (existingNotification) {
    existingNotification.remove();
  }

  // Yeni bildirim oluştur
  const notification = document.createElement("div");
  notification.className = `notification notification-${type}`;

  const icons = {
    success: "fa-check-circle",
    error: "fa-times-circle",
    warning: "fa-exclamation-triangle",
    info: "fa-info-circle",
  };

  notification.innerHTML = `
        <i class="fas ${icons[type]}"></i>
        <span>${message}</span>
    `;

  // Stil ekle
  notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        background: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;

  // Tip bazlı renkler
  const colors = {
    success: "#10B981",
    error: "#EF4444",
    warning: "#F59E0B",
    info: "#3B82F6",
  };

  notification.style.color = colors[type];
  notification.style.borderLeft = `4px solid ${colors[type]}`;

  document.body.appendChild(notification);

  // 3 saniye sonra kaldır
  setTimeout(() => {
    notification.style.animation = "slideOut 0.3s ease";
    setTimeout(() => {
      notification.remove();
    }, 300);
  }, 3000);
}

// Show AI thinking process
function showAIThinking(query) {
  const resultsPanel = document.getElementById("resultsPanel");
  const resultsContainer = document.getElementById("resultsContainer");

  resultsPanel.classList.add("active");
  resultsContainer.innerHTML = `
        <div class="ai-thinking">
            <div class="ai-thinking-header">
                <i class="fas fa-brain text-primary"></i>
                <strong>AI Düşünüyor...</strong>
            </div>
            <div class="ai-thinking-steps">
                <div class="thinking-step active">
                    <i class="fas fa-search"></i>
                    <span>Sorgunuz analiz ediliyor: "${query}"</span>
                </div>
                <div class="thinking-step">
                    <i class="fas fa-filter"></i>
                    <span>Akıllı filtreler oluşturuluyor...</span>
                </div>
                <div class="thinking-step">
                    <i class="fas fa-database"></i>
                    <span>Ürün veritabanında eşleşmeler aranıyor...</span>
                </div>
            </div>
        </div>
    `;

  // Animate thinking steps
  setTimeout(() => {
    const steps = document.querySelectorAll(".thinking-step");
    steps[1]?.classList.add("active");
  }, 800);

  setTimeout(() => {
    const steps = document.querySelectorAll(".thinking-step");
    steps[2]?.classList.add("active");
  }, 1600);
}

// Enhanced search suggestions with AI
function getSearchSuggestions(partialQuery) {
  if (partialQuery.length < 2) return;

  fetch("/ai/suggest", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `query=${encodeURIComponent(partialQuery)}`,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success && data.suggestions.length > 0) {
        showSearchSuggestions(data.suggestions);
      }
    })
    .catch((error) => {
      console.error("Suggestions error:", error);
    });
}

// Show search suggestions dropdown
function showSearchSuggestions(suggestions) {
  let dropdown = document.getElementById("searchSuggestions");
  if (!dropdown) {
    dropdown = document.createElement("div");
    dropdown.id = "searchSuggestions";
    dropdown.className = "search-suggestions";
    document.getElementById("aiPrompt").parentNode.appendChild(dropdown);
  }

  dropdown.innerHTML = suggestions
    .map(
      (suggestion) =>
        `<div class="suggestion-item" onclick="selectSuggestion('${suggestion}')">${suggestion}</div>`
    )
    .join("");

  dropdown.style.display = "block";
}

// Select a suggestion
function selectSuggestion(suggestion) {
  document.getElementById("aiPrompt").value = suggestion;
  document.getElementById("searchSuggestions").style.display = "none";
  searchWithAI();
}

// Add debounced search suggestions to AI prompt
document.addEventListener("DOMContentLoaded", function () {
  const aiPrompt = document.getElementById("aiPrompt");
  if (aiPrompt) {
    let debounceTimer;
    aiPrompt.addEventListener("input", function (e) {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        getSearchSuggestions(e.target.value);
      }, 500);
    });

    // Hide suggestions when clicking outside
    document.addEventListener("click", function (e) {
      if (!e.target.closest(".ai-prompt-container")) {
        const suggestions = document.getElementById("searchSuggestions");
        if (suggestions) suggestions.style.display = "none";
      }
    });
  }
});

// Animasyon stilleri ekle
const style = document.createElement("style");
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }

    .ai-analysis {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .ai-analysis.ai-fallback {
        background: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%);
    }

    .ai-analysis-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
        font-size: 14px;
    }

    .ai-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .ai-filter {
        background: rgba(255,255,255,0.2);
        padding: 4px 8px;
        border-radius: 15px;
        font-size: 12px;
        border: 1px solid rgba(255,255,255,0.3);
    }

    .ai-thinking {
        text-align: center;
        padding: 30px;
    }

    .ai-thinking-header {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-bottom: 20px;
        font-size: 16px;
        color: #667eea;
    }

    .ai-thinking-steps {
        display: flex;
        flex-direction: column;
        gap: 15px;
        max-width: 400px;
        margin: 0 auto;
    }

    .thinking-step {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        border-radius: 8px;
        background: #f8f9fa;
        opacity: 0.5;
        transition: all 0.3s ease;
    }

    .thinking-step.active {
        opacity: 1;
        background: #e3f2fd;
        color: #1976d2;
        transform: scale(1.02);
    }

    .thinking-step i {
        width: 20px;
        text-align: center;
    }

    .product-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 5px;
    }

    .product-brand {
        font-size: 12px;
        background: #667eea;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-weight: 500;
    }

    .product-price {
        font-size: 18px;
        font-weight: bold;
        color: #2e7d32;
        margin: 10px 0;
    }

    .product-features {
        margin: 8px 0;
        font-style: italic;
    }

    .results-count {
        text-align: center;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 8px;
        margin-bottom: 15px;
        color: #666;
    }

    .search-suggestions {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        z-index: 1000;
        max-height: 200px;
        overflow-y: auto;
        display: none;
    }

    .suggestion-item {
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
        transition: background 0.2s;
    }

    .suggestion-item:hover {
        background: #f0f0f0;
    }

    .suggestion-item:last-child {
        border-bottom: none;
    }
`;
document.head.appendChild(style);
