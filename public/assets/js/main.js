// Modern E-commerce Product Add Page JavaScript

// Gemini API Client - Embedded
class GeminiClient {
  constructor(apiKey, model = "gemini-1.5-flash") {
    this.apiKey = apiKey;
    this.model = model;
    this.baseUrl = "https://generativelanguage.googleapis.com/v1beta/models";
  }

  async validateField(fieldName, content, context = {}) {
    const prompts = {
      title: `Evaluate the following product title: "${content}". Is this title clear, descriptive, and suitable for e-commerce? Provide brief feedback and suggestions for improvement.`,
      description: `Review this product description: "${content}". Assess if it's informative, engaging, and provides value to customers. Suggest improvements for clarity and appeal.`,
      metaSeo: `Analyze this SEO meta description: "${content}". Check if it's within 150-160 characters, includes relevant keywords, and is compelling for search results. Provide recommendations.`,
      features: `Evaluate these product features: "${content}". Are they clear, specific, and valuable to customers? Suggest improvements or additional features that might be missing.`,
    };

    const prompt =
      prompts[fieldName] ||
      `Analyze this content: "${content}" and provide feedback.`;

    try {
      const result = await this.generateContent(prompt);
      return this.parseValidationResponse(result, content);
    } catch (error) {
      console.error("Gemini API validation error:", error);
      return {
        isValid: false,
        score: 0,
        message: "Validation service temporarily unavailable",
        suggestions: ["Please try again later"],
      };
    }
  }

  async searchProducts(query) {
    const prompt = `Analyze this search query: "${query}" and extract product search parameters.
                       Return a JSON object with the following structure:
                       {
                         "category": "extracted category or null",
                         "color": "extracted color or null", 
                         "material": "extracted material or null",
                         "brand": "extracted brand or null",
                         "size": "extracted size or null",
                         "style": "extracted style or null",
                         "keywords": ["array", "of", "keywords"],
                         "filters": {
                           "price_range": {"min": null, "max": null},
                           "target_gender": "male/female/unisex or null"
                         }
                       }
                       
                       Only include fields that are clearly mentioned or implied in the query.`;

    try {
      const result = await this.generateContent(prompt);
      return this.parseSearchResponse(result, query);
    } catch (error) {
      console.error("Gemini API search error:", error);
      return {
        success: false,
        originalQuery: query,
        extractedFilters: {},
        fallbackSearch: true,
      };
    }
  }

  async generateContent(prompt) {
    if (!this.isConfigured()) {
      throw new Error(
        "Gemini API key not configured. Please set your API key."
      );
    }

    const url = `${this.baseUrl}/${this.model}:generateContent?key=${this.apiKey}`;

    const requestBody = {
      contents: [
        {
          parts: [
            {
              text: prompt,
            },
          ],
        },
      ],
      generationConfig: {
        temperature: 0.1,
        topK: 1,
        topP: 1,
        maxOutputTokens: 1024,
      },
    };

    const response = await fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(requestBody),
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(
        `Gemini API error: ${error.error?.message || response.statusText}`
      );
    }

    const data = await response.json();

    if (
      !data.candidates ||
      !data.candidates[0] ||
      !data.candidates[0].content
    ) {
      throw new Error("Invalid response from Gemini API");
    }

    return data.candidates[0].content.parts[0].text;
  }

  parseValidationResponse(response, originalContent) {
    try {
      const lines = response.split("\n").filter((line) => line.trim());
      let score = 70;
      let isValid = true;
      let suggestions = [];

      for (const line of lines) {
        const lowerLine = line.toLowerCase();

        if (
          lowerLine.includes("too short") ||
          lowerLine.includes("not enough") ||
          lowerLine.includes("insufficient") ||
          lowerLine.includes("lacking")
        ) {
          score -= 20;
          isValid = false;
        }

        if (
          lowerLine.includes("good") ||
          lowerLine.includes("appropriate") ||
          lowerLine.includes("suitable") ||
          lowerLine.includes("well")
        ) {
          score += 10;
        }

        if (
          lowerLine.includes("suggest") ||
          lowerLine.includes("recommend") ||
          lowerLine.includes("consider") ||
          lowerLine.includes("try")
        ) {
          suggestions.push(line.trim());
        }
      }

      score = Math.max(0, Math.min(100, score));
      isValid = score >= 60;

      return {
        isValid,
        score,
        message: isValid ? "Content looks good!" : "Content needs improvement",
        suggestions: suggestions.length > 0 ? suggestions : [response.trim()],
        originalContent,
        geminiResponse: response,
      };
    } catch (error) {
      console.error("Error parsing validation response:", error);
      return {
        isValid: false,
        score: 0,
        message: "Unable to parse validation response",
        suggestions: ["Please check your content manually"],
        originalContent,
      };
    }
  }

  parseSearchResponse(response, originalQuery) {
    try {
      const jsonMatch = response.match(/\{[\s\S]*\}/);

      if (jsonMatch) {
        const extractedData = JSON.parse(jsonMatch[0]);

        return {
          success: true,
          originalQuery,
          extractedFilters: extractedData,
          fallbackSearch: false,
          geminiResponse: response,
        };
      } else {
        return {
          success: true,
          originalQuery,
          extractedFilters: {
            keywords: originalQuery
              .split(" ")
              .filter((word) => word.length > 2),
          },
          fallbackSearch: true,
          geminiResponse: response,
        };
      }
    } catch (error) {
      console.error("Error parsing search response:", error);
      return {
        success: false,
        originalQuery,
        extractedFilters: {
          keywords: originalQuery.split(" ").filter((word) => word.length > 2),
        },
        fallbackSearch: true,
        error: error.message,
      };
    }
  }

  isConfigured() {
    return this.apiKey && this.apiKey !== "your_gemini_api_key_here";
  }
}

// Note: Gemini AI functionality now handled server-side via backend API
// window.geminiClient = new GeminiClient("API_KEY_MOVED_TO_BACKEND");

class ProductManager {
  constructor() {
    this.baseURL = window.location.origin + "/eticaret-staj/public/";
    this.productId = null;
    this.validationResults = {};
    this.aiPanel = null;
    this.overlay = null;

    this.init();
  }

  init() {
    this.bindEvents();
    this.createAIPanel();
    this.createOverlay();
    this.loadCategories();
  }

  bindEvents() {
    // Form validation on field blur
    $(document).on("blur", ".form-control, .form-select", (e) => {
      this.validateField(e.target);
    });

    // Individual field validation buttons
    $(document).on("click", ".validate-field-btn", (e) => {
      e.preventDefault();
      const fieldName = $(e.currentTarget).data("field");
      this.validateFieldWithGemini(fieldName);
    });

    // Form submission
    $(document).on("click", "#saveProduct", (e) => {
      e.preventDefault();
      this.saveProduct();
    });

    // Validation button
    $(document).on("click", "#validateProduct", (e) => {
      e.preventDefault();
      this.validateAllFields();
    });

    // Add New Product button
    $(document).on("click", "#addNewProduct", (e) => {
      e.preventDefault();
      this.resetFormForNewProduct();
    });

    // AI Assistant button
    $(document).on("click", "#aiAssistant", (e) => {
      e.preventDefault();
      this.toggleAIPanel();
    });

    // Close AI panel
    $(document).on("click", ".close-panel, .overlay", () => {
      this.closeAIPanel();
    });

    // AI search
    $(document).on("click", ".search-btn", () => {
      this.performAISearch();
    });

    $(document).on("keypress", "#aiSearchInput", (e) => {
      if (e.which === 13) {
        this.performAISearch();
      }
    });

    // Search result click - load product for editing
    $(document).on("click", ".search-result-item", (e) => {
      this.loadProductForEditing(e.currentTarget);
    });

    // Quantity controls in search results
    $(document).on("click", ".qty-btn", (e) => {
      e.stopPropagation();
      this.handleQuantityChange(e.currentTarget);
    });

    // Add to cart button
    $(document).on("click", ".add-to-cart-btn", (e) => {
      e.stopPropagation();
      this.addToCart(e.currentTarget);
    });

    // Real-time search suggestions
    $(document).on("input", "#aiSearchInput", () => {
      this.showSearchSuggestions();
    });
  }

  createAIPanel() {
    const panelHTML = `
            <div id="aiPanel" class="ai-panel">
                <div class="ai-panel-header">
                    <h3><i class="fas fa-robot"></i> AI Assistant</h3>
                    <button class="close-panel">&times;</button>
                </div>
                <div class="ai-panel-body">
                    <div class="search-box">
                        <input type="text" id="aiSearchInput" placeholder="Search for products..." class="form-control">
                        <button class="search-btn"><i class="fas fa-search"></i></button>
                    </div>
                    
                    <div id="searchSuggestions" class="search-suggestions" style="display: none;"></div>
                    
                    <div id="loadingSearch" class="loading">
                        <div class="spinner"></div>
                        <p>Searching...</p>
                    </div>
                    
                    <div id="searchResults" class="search-results"></div>
                    
                    <div class="ai-actions">
                        <button id="analyzeProduct" class="btn btn-info btn-sm mb-2 w-100">
                            <i class="fas fa-chart-line"></i> Analyze Current Product
                        </button>
                        <div id="analysisResults"></div>
                    </div>
                </div>
            </div>
        `;

    $("body").append(panelHTML);
    this.aiPanel = $("#aiPanel");

    // Bind analyze button
    $(document).on("click", "#analyzeProduct", () => {
      this.analyzeCurrentProduct();
    });
  }

  createOverlay() {
    $("body").append('<div id="overlay" class="overlay"></div>');
    this.overlay = $("#overlay");
  }

  loadCategories() {
    // Categories should be loaded from the server-side in the view
    // This is just a placeholder for any additional category loading
    console.log("Categories loaded from server-side");
  }

  validateField(field) {
    const fieldName = $(field).attr("name") || $(field).attr("id");
    const fieldValue = $(field).val();

    if (!fieldName || !fieldValue) return;

    const data = {
      field_name: fieldName,
      field_value: fieldValue,
      product_id: this.productId || 0,
    };

    $.ajax({
      url: this.baseURL + "product/validate-field",
      method: "POST",
      data: data,
      dataType: "json",
      success: (response) => {
        this.handleValidationResponse(fieldName, response);
      },
      error: (xhr) => {
        console.error("Validation error:", xhr);
        this.showFieldError(fieldName, "Validation failed");
      },
    });
  }

  async validateFieldWithGemini(fieldName) {
    const field = $(`[name="${fieldName}"], #${fieldName}`);
    const content = field.val().trim();

    if (!content) {
      this.showAlert("warning", "Please enter some content before validation.");
      return;
    }

    const validateBtn = $(`.validate-field-btn[data-field="${fieldName}"]`);
    const originalText = validateBtn.html();
    validateBtn.html('<i class="fas fa-spinner fa-spin"></i> Validating...');
    validateBtn.prop("disabled", true);

    try {
      // Get category for context
      const category = $("#category").find("option:selected").text() || "";

      // Call backend API for validation
      const response = await $.ajax({
        url: this.baseURL + "ai/validateField",
        method: "POST",
        data: {
          field_name: fieldName,
          content: content,
          category: category,
        },
        dataType: "json",
      });

      if (response.success) {
        // Show result in alert
        let message = `<strong>AI Validation Analysis:</strong><br>`;
        message += `<div style="background: #f8f9fa; padding: 10px; border-left: 4px solid #007bff; margin: 10px 0;">${response.analysis}</div>`;

        this.showAlert("info", message);

        // Update field appearance with positive feedback
        field.removeClass("is-invalid").addClass("is-valid");
        field.siblings(".validation-feedback").remove();
        field.after(
          `<div class="validation-feedback text-success">✓ AI Analysis completed</div>`
        );

        // Store validation result
        this.validationResults[fieldName] = response;
      } else {
        throw new Error(response.error || "Validation failed");
      }
    } catch (error) {
      console.error("Validation error:", error);
      this.showAlert("danger", `Validation failed: ${error.message || error}`);

      field.removeClass("is-valid").addClass("is-invalid");
      field.siblings(".validation-feedback").remove();
      field.after(
        `<div class="validation-feedback text-danger">Validation failed</div>`
      );
    } finally {
      validateBtn.html(originalText);
      validateBtn.prop("disabled", false);
    }
  }

  handleValidationResponse(fieldName, response) {
    const field = $(`[name="${fieldName}"], #${fieldName}`);
    const feedbackElement = field.siblings(".validation-feedback");

    // Remove existing validation classes
    field.removeClass("is-valid is-invalid");

    // Remove existing feedback
    feedbackElement.remove();

    if (response.is_valid) {
      field.addClass("is-valid");
      field.after(
        `<div class="validation-feedback valid-feedback">${response.message}</div>`
      );
    } else {
      field.addClass("is-invalid");
      field.after(
        `<div class="validation-feedback invalid-feedback">${response.message}</div>`
      );
    }

    // Store validation result
    this.validationResults[fieldName] = response.is_valid;
  }

  showFieldError(fieldName, message) {
    const field = $(`[name="${fieldName}"], #${fieldName}`);
    field.removeClass("is-valid").addClass("is-invalid");
    field.siblings(".validation-feedback").remove();
    field.after(
      `<div class="validation-feedback invalid-feedback">${message}</div>`
    );

    this.validationResults[fieldName] = false;
  }

  validateAllFields() {
    this.showAlert("info", "Validating all fields...");

    const fields = ["title", "category_id", "description", "price", "stock"];
    let validatedCount = 0;

    fields.forEach((fieldName) => {
      const field = $(`[name="${fieldName}"], #${fieldName}`);
      if (field.length && field.val()) {
        this.validateField(field[0]);
        validatedCount++;
      }
    });

    if (validatedCount === 0) {
      this.showAlert("warning", "Please fill in some fields before validation");
    } else {
      setTimeout(() => {
        this.checkValidationStatus();
      }, 1000);
    }
  }

  checkValidationStatus() {
    const requiredFields = ["title", "category_id", "description", "price"];
    const validFields = requiredFields.filter(
      (field) => this.validationResults[field] === true
    );

    if (validFields.length === requiredFields.length) {
      this.showAlert(
        "success",
        "All required fields are valid! Ready to save."
      );
      $("#saveProduct")
        .removeClass("btn-primary")
        .addClass("btn-success pulse");
    } else {
      const invalidCount = requiredFields.length - validFields.length;
      this.showAlert(
        "warning",
        `${invalidCount} field(s) need attention before saving.`
      );
    }
  }

  saveProduct() {
    // Validate required fields
    const requiredFields = [
      "title",
      "brand",
      "category_id",
      "description",
      "price",
    ];
    const missingFields = requiredFields.filter(
      (field) => !$(`[name="${field}"]`).val()
    );

    if (missingFields.length > 0) {
      this.showAlert(
        "danger",
        "Please fill in all required fields: " + missingFields.join(", ")
      );
      return;
    }

    const formData = this.getFormData();
    const isUpdate = this.productId !== null;
    const url = isUpdate
      ? this.baseURL + `product/update/${this.productId}`
      : this.baseURL + "product/save";
    const action = isUpdate ? "Updating" : "Saving";

    this.showAlert("info", `${action} product...`);
    $("#saveProduct")
      .prop("disabled", true)
      .html(`<i class="fas fa-spinner fa-spin"></i> ${action}...`);

    $.ajax({
      url: url,
      method: "POST",
      data: formData,
      dataType: "json",
      success: (response) => {
        if (response.success) {
          if (!isUpdate && response.product_id) {
            this.productId = response.product_id;
          }

          this.showAlert("success", response.message);

          if (!isUpdate) {
            // For new products, clear form after success
            setTimeout(() => {
              this.resetFormForNewProduct();
            }, 2000);
          }

          // Animate success
          $("#saveProduct").removeClass("pulse").addClass("btn-success");
          setTimeout(() => {
            if (isUpdate) {
              $("#saveProduct")
                .removeClass("btn-success")
                .addClass("btn-warning");
            } else {
              $("#saveProduct")
                .removeClass("btn-success")
                .addClass("btn-primary");
            }
          }, 3000);
        } else {
          this.showAlert("danger", response.message);
          if (response.errors) {
            this.handleValidationErrors(response.errors);
          }
        }
      },
      error: (xhr) => {
        console.error("Save error:", xhr);
        this.showAlert(
          "danger",
          `Failed to ${action.toLowerCase()} product. Please try again.`
        );
      },
      complete: () => {
        const buttonText = isUpdate
          ? '<i class="fas fa-edit"></i> Update Product'
          : '<i class="fas fa-save"></i> Save Product';
        $("#saveProduct").prop("disabled", false).html(buttonText);
      },
    });
  }

  getFormData() {
    const formData = {};

    // Get all form fields
    $("#productForm input, #productForm select, #productForm textarea").each(
      function () {
        const name = $(this).attr("name");
        if (name) {
          formData[name] = $(this).val();
        }
      }
    );

    return formData;
  }

  handleValidationErrors(errors) {
    Object.keys(errors).forEach((fieldName) => {
      this.showFieldError(fieldName, errors[fieldName]);
    });
  }

  resetForm() {
    $("#productForm")[0].reset();
    $(".form-control, .form-select").removeClass("is-valid is-invalid");
    $(".validation-feedback").remove();
    this.validationResults = {};
    this.productId = null;
  }

  toggleAIPanel() {
    if (this.aiPanel.hasClass("open")) {
      this.closeAIPanel();
    } else {
      this.openAIPanel();
    }
  }

  openAIPanel() {
    this.aiPanel.addClass("open slide-in-right");
    this.overlay.addClass("show");
    $("#aiSearchInput").focus();
  }

  closeAIPanel() {
    this.aiPanel.removeClass("open");
    this.overlay.removeClass("show");
    $("#searchResults").empty();
    $("#aiSearchInput").val("");
  }

  async performAISearch() {
    const query = $("#aiSearchInput").val().trim();

    if (!query) {
      this.showAlert("warning", "Please enter a search query");
      return;
    }

    $("#loadingSearch").show();
    $("#searchResults").empty();

    try {
      // Call backend API which will use Gemini to analyze the query and search
      const response = await $.ajax({
        url: this.baseURL + "ai/search",
        method: "POST",
        data: {
          query: query,
        },
        dataType: "json",
      });

      if (response.success) {
        // Display AI analysis results first
        let analysisHTML = `
          <div class="ai-analysis mb-3">
            <h6><i class="fas fa-brain"></i> AI Query Analysis</h6>
            <div class="alert alert-info">
              <strong>Original Query:</strong> "${response.query}"<br>
              <strong>Analysis Status:</strong> ${
                response.gemini_analysis.success ? "✅ Success" : "❌ Failed"
              }<br>
        `;

        if (
          response.gemini_analysis.success &&
          response.gemini_analysis.extracted_filters
        ) {
          analysisHTML += `<strong>Extracted Filters:</strong><br>`;

          const filters = response.gemini_analysis.extracted_filters;
          let filterDisplay = [];

          Object.keys(filters).forEach((key) => {
            if (filters[key] && filters[key] !== null && filters[key] !== "") {
              if (Array.isArray(filters[key]) && filters[key].length > 0) {
                filterDisplay.push(
                  `<strong>${key}:</strong> ${filters[key].join(", ")}`
                );
              } else if (!Array.isArray(filters[key])) {
                filterDisplay.push(`<strong>${key}:</strong> ${filters[key]}`);
              }
            }
          });

          if (filterDisplay.length > 0) {
            analysisHTML += filterDisplay.join("<br>");
          } else {
            analysisHTML += "<em>No specific filters extracted</em>";
          }
        } else if (response.gemini_analysis.fallback_used) {
          const fallbackType =
            response.gemini_analysis.fallback_type || "basic";
          analysisHTML += `<strong>Note:</strong> AI analysis ${
            fallbackType === "smart"
              ? "used smart fallback"
              : "failed, using basic search"
          }<br>`;
          if (response.gemini_analysis.error) {
            analysisHTML += `<strong>AI Error:</strong> ${response.gemini_analysis.error}<br>`;
          }

          // Show extracted filters even in fallback mode
          if (response.gemini_analysis.extracted_filters) {
            analysisHTML += `<strong>Detected Filters:</strong><br>`;
            const filters = response.gemini_analysis.extracted_filters;
            let filterDisplay = [];

            Object.keys(filters).forEach((key) => {
              if (
                filters[key] &&
                filters[key] !== null &&
                filters[key] !== ""
              ) {
                if (Array.isArray(filters[key]) && filters[key].length > 0) {
                  filterDisplay.push(
                    `<strong>${key}:</strong> ${filters[key].join(", ")}`
                  );
                } else if (!Array.isArray(filters[key])) {
                  filterDisplay.push(
                    `<strong>${key}:</strong> ${filters[key]}`
                  );
                }
              }
            });

            if (filterDisplay.length > 0) {
              analysisHTML += filterDisplay.join("<br>");
            } else {
              analysisHTML += "<em>Using keyword-based search</em>";
            }
          }
        }

        analysisHTML += `
            </div>
          </div>
        `;

        $("#searchResults").html(analysisHTML);

        // Display search results
        if (response.products && response.products.length > 0) {
          this.appendSearchResults(response);
        } else {
          $("#searchResults").append(`
            <div class="alert alert-warning">
              <i class="fas fa-search"></i> 
              No products found matching your query, but AI analysis above shows how your query was interpreted.
            </div>
          `);
        }
      } else {
        $("#searchResults").html(`
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> 
            Search failed: ${response.message || "Unknown error"}
          </div>
        `);
      }
    } catch (error) {
      console.error("AI Search error:", error);
      $("#searchResults").html(`
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-triangle"></i> 
          AI Search failed: ${
            error.responseJSON?.message || error.message || "Connection error"
          }
        </div>
      `);
    } finally {
      $("#loadingSearch").hide();
    }
  }

  appendSearchResults(data) {
    if (data.products && data.products.length > 0) {
      const resultsHTML = data.products
        .map(
          (product) => `
        <div class="search-result-item fade-in" data-product='${JSON.stringify(
          product
        )}' data-product-id="${product.id}">
          <div class="product-header">
            <div class="search-result-brand">
              <i class="fas fa-award"></i> ${product.brand || "No Brand"}
            </div>
            <div class="search-result-title">${product.title}</div>
          </div>
          
          <div class="product-details">
            <div class="search-result-category">
              <i class="fas fa-tags"></i> ${
                product.category_name || "No category"
              }
            </div>
            <div class="search-result-stock">
              <i class="fas fa-boxes"></i> Stock: ${product.stock}
            </div>
            <div class="search-result-price">
              <i class="fas fa-dollar-sign"></i> $${parseFloat(
                product.price
              ).toFixed(2)}
            </div>
          </div>
          
          <div class="edit-hint">
            <small class="text-muted">
              <i class="fas fa-edit"></i> Click to load for editing
            </small>
          </div>
        </div>
      `
        )
        .join("");

      $("#searchResults").append(`
        <div class="database-results">
          <h6><i class="fas fa-database"></i> Database Results (${data.products.length})</h6>
          <div class="products-grid">
            ${resultsHTML}
          </div>
        </div>
      `);
    } else {
      $("#searchResults").append(`
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i> 
          No products found in database matching your query.
        </div>
      `);
    }
  }

  displaySearchResults(data) {
    const resultsContainer = $("#searchResults");
    resultsContainer.empty();

    if (data.products && data.products.length > 0) {
      const resultsHTML = data.products
        .map(
          (product) => `
                <div class="search-result-item fade-in" data-product='${JSON.stringify(
                  product
                )}' data-product-id="${product.id}">
                    <div class="product-header">
                        <div class="search-result-brand">
                            <i class="fas fa-award"></i> ${
                              product.brand || "No Brand"
                            }
                        </div>
                        <div class="search-result-title">${product.title}</div>
                    </div>
                    
                    <div class="product-details">
                        <div class="search-result-category">
                            <i class="fas fa-tags"></i> ${
                              product.category_name || "No category"
                            }
                        </div>
                        <div class="search-result-stock">
                            <i class="fas fa-boxes"></i> Stock: ${product.stock}
                        </div>
                        <div class="search-result-price">
                            <i class="fas fa-dollar-sign"></i> $${parseFloat(
                              product.price
                            ).toFixed(2)}
                        </div>
                    </div>
                    
                    <div class="product-controls">
                        <div class="quantity-controls">
                            <button class="qty-btn qty-minus" data-action="minus">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" class="qty-input" value="1" min="1" max="${
                              product.stock
                            }" readonly>
                            <button class="qty-btn qty-plus" data-action="plus">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <button class="add-to-cart-btn btn btn-sm btn-success">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                    </div>
                    
                    <div class="edit-hint">
                        <small class="text-muted">
                            <i class="fas fa-edit"></i> Click to load for editing
                        </small>
                    </div>
                </div>
            `
        )
        .join("");

      resultsContainer.html(`
                <h6><i class="fas fa-search"></i> Search Results (${data.products.length})</h6>
                <div class="products-grid">
                    ${resultsHTML}
                </div>
            `);
    } else {
      resultsContainer.html(`
                <div class="text-center text-muted py-3">
                    <i class="fas fa-search fa-2x mb-2"></i>
                    <p>No products found for "${data.query}"</p>
                </div>
            `);
    }

    // Show suggestions if available
    if (data.suggestions && data.suggestions.length > 0) {
      const suggestionsHTML = data.suggestions
        .map(
          (suggestion) => `
                <p class="small text-info"><i class="fas fa-lightbulb"></i> ${suggestion}</p>
            `
        )
        .join("");

      resultsContainer.append(`
                <div class="mt-3">
                    <h6><i class="fas fa-lightbulb"></i> Suggestions</h6>
                    ${suggestionsHTML}
                </div>
            `);
    }
  }

  loadProductForEditing(element) {
    const productData = JSON.parse($(element).attr("data-product"));

    // Fill form with selected product data for editing
    $("#title").val(productData.title);
    $("#brand").val(productData.brand || "");
    $("#category_id").val(productData.category_id);
    $("#description").val(productData.description);
    $("#price").val(productData.price);
    $("#stock").val(productData.stock);
    $("#features").val(productData.features || "");
    $("#meta_seo").val(productData.meta_seo || "");

    // Store product ID for updating
    this.productId = productData.id;

    // Update button text and color
    $("#saveProduct")
      .html('<i class="fas fa-edit"></i> Update Product')
      .removeClass("btn-success")
      .addClass("btn-warning");

    this.showAlert(
      "info",
      `Loaded "${productData.title}" for editing. Make your changes and click Update Product.`
    );

    // Close panel
    this.closeAIPanel();

    // Scroll to form
    $("html, body").animate(
      {
        scrollTop: $("#productForm").offset().top - 100,
      },
      800
    );
  }

  resetFormForNewProduct() {
    // Reset form
    $("#productForm")[0].reset();

    // Clear validation states
    $(".form-control, .form-select").removeClass("is-valid is-invalid");
    $(".validation-feedback").remove();

    // Reset button
    $("#saveProduct")
      .html('<i class="fas fa-save"></i> Save Product')
      .removeClass("btn-warning")
      .addClass("btn-success");

    // Clear product ID
    this.productId = null;
    this.validationResults = {};

    this.showAlert("success", "Form cleared for new product entry.");
  }

  handleQuantityChange(button) {
    const action = $(button).data("action");
    const qtyInput = $(button).closest(".quantity-controls").find(".qty-input");
    let currentQty = parseInt(qtyInput.val());
    const maxQty = parseInt(qtyInput.attr("max"));

    if (action === "plus" && currentQty < maxQty) {
      qtyInput.val(currentQty + 1);
    } else if (action === "minus" && currentQty > 1) {
      qtyInput.val(currentQty - 1);
    }
  }

  addToCart(button) {
    const productItem = $(button).closest(".search-result-item");
    const productData = JSON.parse(productItem.attr("data-product"));
    const quantity = parseInt(productItem.find(".qty-input").val());

    // Simulate adding to cart (you can implement actual cart functionality)
    this.showAlert(
      "success",
      `Added ${quantity}x "${productData.title}" to cart! (${quantity} × $${
        productData.price
      } = $${(quantity * productData.price).toFixed(2)})`
    );

    // You could implement actual cart functionality here:
    // - Store in localStorage
    // - Send to backend API
    // - Update cart counter, etc.
    console.log("Adding to cart:", {
      product: productData,
      quantity: quantity,
      total: quantity * productData.price,
    });
  }

  showSearchSuggestions() {
    const query = $("#aiSearchInput").val().trim();

    if (query.length < 2) {
      $("#searchSuggestions").hide();
      return;
    }

    const data = {
      partial: query,
    };

    $.ajax({
      url: this.baseURL + "ai/suggest",
      method: "POST",
      data: data,
      dataType: "json",
      success: (response) => {
        if (response.success && response.suggestions.length > 0) {
          this.displaySuggestions(response.suggestions);
        } else {
          $("#searchSuggestions").hide();
        }
      },
      error: () => {
        $("#searchSuggestions").hide();
      },
    });
  }

  displaySuggestions(suggestions) {
    const suggestionsHTML = suggestions
      .map(
        (suggestion) => `
            <div class="suggestion-item" data-text="${suggestion.text}">
                <i class="fas fa-${
                  suggestion.type === "product" ? "box" : "tags"
                }"></i>
                ${suggestion.text}
                <small class="text-muted">(${suggestion.type})</small>
            </div>
        `
      )
      .join("");

    $("#searchSuggestions").html(suggestionsHTML).show();

    // Handle suggestion click
    $(document).on("click", ".suggestion-item", function () {
      const text = $(this).data("text");
      $("#aiSearchInput").val(text);
      $("#searchSuggestions").hide();
    });
  }

  analyzeCurrentProduct() {
    const productData = this.getFormData();

    if (!productData.title || !productData.description) {
      this.showAlert(
        "warning",
        "Please fill in title and description before analysis"
      );
      return;
    }

    $("#analysisResults").html(
      '<div class="loading"><div class="spinner"></div><p>Analyzing...</p></div>'
    );

    $.ajax({
      url: this.baseURL + "ai/analyze",
      method: "POST",
      data: productData, // Send form data directly
      dataType: "json",
      success: (response) => {
        if (response.success) {
          this.displayAnalysisResults(response.analysis);
        } else {
          this.showAlert("danger", response.error || "Analysis failed");
          $("#analysisResults").html(`
            <div class="alert alert-warning">
              <i class="fas fa-exclamation-triangle"></i> 
              Analysis failed: ${response.error || "Unknown error"}
            </div>
          `);
        }
      },
      error: (xhr) => {
        console.error("Analysis error:", xhr);
        this.showAlert("danger", "Analysis failed. Please try again.");
        $("#analysisResults").html(`
          <div class="alert alert-danger">
            <i class="fas fa-times-circle"></i> 
            Failed to connect to AI analysis service
          </div>
        `);
      },
    });
  }

  displayAnalysisResults(analysis) {
    // Simple text-based analysis from Gemini API
    let analysisHTML = `
      <div class="analysis-results">
        <h6><i class="fas fa-chart-line"></i> AI Product Analysis</h6>
        <div class="alert alert-info">
          <div style="white-space: pre-wrap; line-height: 1.6;">${analysis}</div>
        </div>
      </div>
    `;

    $("#analysisResults").html(analysisHTML);
  }

  getScoreBadgeClass(percentage) {
    if (percentage >= 80) return "success";
    if (percentage >= 60) return "warning";
    return "danger";
  }

  getPriorityClass(priority) {
    switch (priority) {
      case "high":
        return "danger";
      case "medium":
        return "warning";
      case "low":
        return "info";
      default:
        return "secondary";
    }
  }

  showAlert(type, message) {
    // Remove existing alerts
    $(".alert").fadeOut(300, function () {
      $(this).remove();
    });

    const alertHTML = `
            <div class="alert alert-${type} fade-in">
                <i class="fas fa-${this.getAlertIcon(type)}"></i>
                ${message}
            </div>
        `;

    $("#alertContainer").prepend(alertHTML);

    // Auto-hide success and info alerts
    if (type === "success" || type === "info") {
      setTimeout(() => {
        $(".alert-" + type).fadeOut(300, function () {
          $(this).remove();
        });
      }, 5000);
    }
  }

  getAlertIcon(type) {
    switch (type) {
      case "success":
        return "check-circle";
      case "danger":
        return "exclamation-triangle";
      case "warning":
        return "exclamation-circle";
      case "info":
        return "info-circle";
      default:
        return "info-circle";
    }
  }
}

// Initialize when document is ready
$(document).ready(function () {
  window.productManager = new ProductManager();

  // Add some initial animations
  $(".main-card").addClass("fade-in");

  console.log("E-commerce Product Manager initialized successfully!");
});
