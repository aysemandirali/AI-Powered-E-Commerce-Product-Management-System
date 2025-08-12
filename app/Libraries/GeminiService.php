<?php

namespace App\Libraries;

use Exception;

class GeminiService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct()
    {
        $this->apiKey = env('gemini.apiKey', '');
        $this->model = env('gemini.model', 'gemini-1.5-flash');

        if (empty($this->apiKey) || $this->apiKey === 'your_gemini_api_key_here') {
            log_message('warning', 'Gemini API key not configured - AI features will use fallback responses');
        }
    }

    /**
     * Check if Gemini API is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && $this->apiKey !== 'your_gemini_api_key_here';
    }

    /**
     * Validate product field content with Gemini AI
     */
    public function validateField(string $fieldName, string $content, string $category = null): array
    {
        $prompts = [
            'title' => "Evaluate this product title: '{$content}'\n\nIs this title:\n- Clear and descriptive?\n- Appealing to customers?\n- SEO-friendly?\n- Appropriate length (not too short/long)?\n\nProvide specific suggestions for improvement if needed. Keep response under 150 words.",

            'description' => "Analyze this product description: '{$content}'\n\nEvaluate:\n- Clarity and informativeness\n- Customer appeal\n- Missing important details\n- Grammar and readability\n- Length appropriateness\n\nProvide constructive feedback and suggestions. Keep response under 200 words.",

            'meta_seo' => "Review this SEO meta description: '{$content}'\n\nCheck for:\n- Length (should be 150-160 characters)\n- Keyword optimization\n- Call-to-action presence\n- Compelling language\n- Search engine guidelines compliance\n\nProvide specific recommendations. Keep response under 150 words.",

            'features' => "Evaluate these product features: '{$content}'\n\nAssess:\n- Completeness and relevance\n- Customer value proposition\n- Technical accuracy\n- Clarity of presentation\n- Missing key features\n\nSuggest improvements or additions. Keep response under 150 words.",

            'price' => "Analyze this product price: \${$content}\n\nConsider:\n- Market competitiveness\n- Value perception\n- Pricing psychology\n- Decimal placement strategy\n\nProvide brief pricing insights. Keep response under 100 words.",

            'brand' => "Evaluate this brand name: '{$content}'\n\nCheck for:\n- Brand recognition\n- Spelling accuracy\n- Market presence\n- Consumer trust factors\n\nProvide brief brand assessment. Keep response under 100 words."
        ];

        $prompt = $prompts[$fieldName] ?? "Analyze this {$fieldName}: '{$content}'. Provide brief evaluation and suggestions.";

        if ($category) {
            $prompt .= " Note: This is for a {$category} product.";
        }

        try {
            if (!$this->isConfigured()) {
                return $this->getFallbackValidation($fieldName, $content);
            }

            $response = $this->generateContent($prompt);
            return [
                'success' => true,
                'field' => $fieldName,
                'original_content' => $content,
                'analysis' => $response,
                'category' => $category
            ];
        } catch (Exception $e) {
            log_message('error', 'Gemini validation failed: ' . $e->getMessage());

            // Return fallback validation instead of complete failure
            $fallback = $this->getFallbackValidation($fieldName, $content);
            $fallback['note'] = 'AI validation unavailable, using basic validation';
            $fallback['original_error'] = $e->getMessage();
            return $fallback;
        }
    }

    /**
     * Convert natural language search query to structured filters with semantic understanding
     */
    public function parseSearchQuery(string $query): array
    {
        $prompt = "You are an intelligent product search assistant. Analyze this search query and extract semantic meaning: '{$query}'

Transform this into structured product filters. Use your understanding to interpret intent:

Examples:
- \"ucuz telefon\" → price_range: budget, category: phone
- \"kadınlar için kırmızı elbise\" → gender: female, color: red, category: dress
- \"Apple marka pahalı kulaklık\" → brand: Apple, price_range: premium, category: headphones
- \"erkek spor ayakkabı\" → gender: male, style: sport, category: shoes
- \"gaming laptop under 1000\" → category: laptop, price_range: budget, keywords: [\"gaming\"]

Return ONLY valid JSON with these fields (include only if clearly mentioned or strongly implied):
{
  \"category\": \"product type (phone, shirt, laptop, headphones, dress, shoes, etc)\",
  \"brand\": \"brand name if mentioned\",
  \"color\": \"color if mentioned\",
  \"material\": \"material like cotton, leather, metal, etc\",
  \"gender\": \"male, female, or unisex\",
  \"price_range\": \"budget, medium, or premium (based on words like ucuz, pahalı, budget, expensive)\",
  \"style\": \"casual, formal, sport, gaming, professional, etc\",
  \"size\": \"size if mentioned (S, M, L, XL, or number sizes)\",
  \"keywords\": [\"important search terms and synonyms\"]
}

Be intelligent about synonyms and context. Turkish/English mixed queries are common.
Response must be valid JSON only, no markdown, no explanations.";

        try {
            if (!$this->isConfigured()) {
                $smartFilters = $this->createSmartFallback($query);
                return [
                    'success' => false,
                    'original_query' => $query,
                    'error' => 'AI service not configured',
                    'extracted_filters' => $smartFilters,
                    'fallback_keywords' => $this->extractBasicKeywords($query),
                    'fallback_used' => true,
                    'fallback_type' => 'smart'
                ];
            }

            $response = $this->generateContent($prompt);

            // Clean and parse response more robustly
            $filters = $this->parseJsonResponse($response);

            if ($filters === null) {
                throw new Exception('Failed to parse valid JSON from Gemini response');
            }

            // Enhance filters with additional semantic understanding
            $filters = $this->enhanceFiltersWithSemantics($query, $filters);

            return [
                'success' => true,
                'original_query' => $query,
                'extracted_filters' => $filters,
                'has_filters' => !empty(array_filter($filters)),
                'ai_enhanced' => true
            ];

        } catch (Exception $e) {
            log_message('error', 'Gemini search parsing failed: ' . $e->getMessage());

            // Use smart fallback instead of just keywords
            $smartFilters = $this->createSmartFallback($query);

            return [
                'success' => false,
                'original_query' => $query,
                'error' => $e->getMessage(),
                'extracted_filters' => $smartFilters,
                'fallback_keywords' => $this->extractBasicKeywords($query),
                'fallback_used' => true,
                'fallback_type' => 'smart'
            ];
        }
    }

    /**
     * Analyze complete product data for quality and completeness
     */
    public function analyzeProduct(array $productData): array
    {
        $prompt = "Analyze this complete product data for quality and completeness:\n\n";
        $prompt .= "Title: " . ($productData['title'] ?? 'Not provided') . "\n";
        $prompt .= "Brand: " . ($productData['brand'] ?? 'Not provided') . "\n";
        $prompt .= "Category: " . ($productData['category'] ?? 'Not provided') . "\n";
        $prompt .= "Description: " . ($productData['description'] ?? 'Not provided') . "\n";
        $prompt .= "Features: " . ($productData['features'] ?? 'Not provided') . "\n";
        $prompt .= "Price: $" . ($productData['price'] ?? 'Not provided') . "\n";
        $prompt .= "Stock: " . ($productData['stock'] ?? 'Not provided') . "\n";
        $prompt .= "SEO Meta: " . ($productData['meta_seo'] ?? 'Not provided') . "\n\n";

        $prompt .= "Provide a comprehensive analysis covering:\n";
        $prompt .= "1. Overall completeness score (1-10)\n";
        $prompt .= "2. Key strengths\n";
        $prompt .= "3. Critical gaps or weaknesses\n";
        $prompt .= "4. Specific improvement recommendations\n";
        $prompt .= "5. Market readiness assessment\n\n";
        $prompt .= "Keep response structured and under 300 words.";

        try {
            if (!$this->isConfigured()) {
                return [
                    'success' => true,
                    'analysis' => $this->getFallbackAnalysis($productData),
                    'product_data' => $productData,
                    'analysis_type' => 'fallback'
                ];
            }

            $response = $this->generateContent($prompt);
            return [
                'success' => true,
                'analysis' => $response,
                'product_data' => $productData,
                'analysis_type' => 'ai'
            ];
        } catch (Exception $e) {
            log_message('error', 'Gemini analysis failed: ' . $e->getMessage());

            return [
                'success' => true,
                'analysis' => $this->getFallbackAnalysis($productData),
                'product_data' => $productData,
                'analysis_type' => 'fallback',
                'original_error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate content using Gemini API with fallback to file_get_contents
     */
    private function generateContent(string $prompt): string
    {
        $url = "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}";

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'topK' => 1,
                'topP' => 0.8,
                'maxOutputTokens' => 512
            ],
            'safetySettings' => [
                [
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_HATE_SPEECH',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ]
            ]
        ];

        // Use cURL if available, otherwise fallback to file_get_contents
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'User-Agent: E-Commerce-AI/1.0'
                ],
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);

            curl_close($ch);

            if ($curlError) {
                throw new Exception("Network error: {$curlError}");
            }
        } else {
            // Fallback to file_get_contents
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Content-Type: application/json',
                        'User-Agent: E-Commerce-AI/1.0'
                    ],
                    'content' => json_encode($data),
                    'timeout' => 15
                ]
            ]);

            $response = file_get_contents($url, false, $context);

            if ($response === false) {
                throw new Exception("Network error: Failed to connect to Gemini API");
            }

            // Extract HTTP status code from headers
            $httpCode = 200; // Default to success for file_get_contents
            if (isset($http_response_header)) {
                foreach ($http_response_header as $header) {
                    if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                        $httpCode = intval($matches[1]);
                        break;
                    }
                }
            }
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = 'Unknown error';

            if (isset($errorData['error']['message'])) {
                $errorMessage = $errorData['error']['message'];
            } elseif ($httpCode === 403) {
                $errorMessage = 'API key invalid or quota exceeded';
            } elseif ($httpCode === 429) {
                $errorMessage = 'Rate limit exceeded';
            } elseif ($httpCode >= 500) {
                $errorMessage = 'Gemini service temporarily unavailable';
            }

            throw new Exception("Gemini API error (HTTP {$httpCode}): {$errorMessage}");
        }

        $responseData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from Gemini API');
        }

        if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            // Check for safety filters or other response issues
            if (isset($responseData['candidates'][0]['finishReason'])) {
                $reason = $responseData['candidates'][0]['finishReason'];
                if ($reason === 'SAFETY') {
                    throw new Exception('Response blocked by safety filters');
                } elseif ($reason === 'RECITATION') {
                    throw new Exception('Response blocked due to recitation concerns');
                }
            }
            throw new Exception('Invalid response format from Gemini API');
        }

        return trim($responseData['candidates'][0]['content']['parts'][0]['text']);
    }

    /**
     * Fallback keyword extraction for failed AI parsing
     */
    private function extractBasicKeywords(string $query): array
    {
        // Simple keyword extraction as fallback
        $words = str_word_count(strtolower($query), 1);
        $stopWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        return array_values(array_diff($words, $stopWords));
    }

    /**
     * Enhanced keyword extraction with Turkish support
     */
    private function extractEnhancedKeywords(string $query): array
    {
        $query = strtolower(trim($query));

        // Split by spaces and common separators
        $words = preg_split('/[\s,.-]+/', $query, -1, PREG_SPLIT_NO_EMPTY);

        // Turkish and English stop words
        $stopWords = [
            'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'a', 'an',
            've', 'veya', 'ile', 'için', 'bir', 'bu', 'şu', 'o', 'da', 'de', 'ta', 'te', 'ya', 'ye'
        ];

        $keywords = [];
        foreach ($words as $word) {
            $word = trim($word);
            // Keep words that are longer than 2 characters and not stop words
            if (strlen($word) > 2 && !in_array($word, $stopWords)) {
                $keywords[] = $word;
            }
        }

        return array_unique($keywords);
    }

    /**
     * Provide basic validation when AI is not available
     */
    private function getFallbackValidation(string $fieldName, string $content): array
    {
        $validations = [
            'title' => [
                'min_length' => 3,
                'max_length' => 255,
                'message' => 'Product title should be clear, descriptive, and between 3-255 characters. Consider including key features or benefits.'
            ],
            'description' => [
                'min_length' => 10,
                'max_length' => 2000,
                'message' => 'Product description should be detailed and informative. Include key features, benefits, and usage information to help customers make informed decisions.'
            ],
            'meta_seo' => [
                'min_length' => 50,
                'max_length' => 160,
                'message' => 'SEO meta description should be 50-160 characters, include primary keywords, and encourage clicks with compelling language.'
            ],
            'features' => [
                'min_length' => 5,
                'max_length' => 1000,
                'message' => 'Product features should list key characteristics that set this product apart. Use bullet points or clear formatting for better readability.'
            ],
            'brand' => [
                'min_length' => 2,
                'max_length' => 100,
                'message' => 'Brand name should be accurate and recognizable. Ensure correct spelling and capitalization.'
            ],
            'price' => [
                'numeric' => true,
                'min_value' => 0.01,
                'message' => 'Price should be a positive number. Consider competitive pricing and value perception.'
            ]
        ];

        $rules = $validations[$fieldName] ?? ['message' => 'Field appears to be valid.'];
        $length = strlen($content);
        $isValid = true;
        $issues = [];

        // Basic validation checks
        if (isset($rules['min_length']) && $length < $rules['min_length']) {
            $isValid = false;
            $issues[] = "Content is too short (minimum {$rules['min_length']} characters)";
        }

        if (isset($rules['max_length']) && $length > $rules['max_length']) {
            $isValid = false;
            $issues[] = "Content is too long (maximum {$rules['max_length']} characters)";
        }

        if (isset($rules['numeric']) && !is_numeric($content)) {
            $isValid = false;
            $issues[] = "Must be a valid number";
        }

        if (isset($rules['min_value']) && is_numeric($content) && floatval($content) < $rules['min_value']) {
            $isValid = false;
            $issues[] = "Value must be at least {$rules['min_value']}";
        }

        $analysis = $isValid ?
            "✅ Basic validation passed. " . $rules['message'] :
            "⚠️ Issues found: " . implode(', ', $issues) . "\n\n" . $rules['message'];

        return [
            'success' => true,
            'field' => $fieldName,
            'original_content' => $content,
            'analysis' => $analysis,
            'validation_type' => 'fallback',
            'is_basic_validation' => true
        ];
    }

    /**
     * Provide basic product analysis when AI is not available
     */
    private function getFallbackAnalysis(array $productData): string
    {
        $analysis = "📊 BASIC PRODUCT ANALYSIS (AI Unavailable)\n\n";

        // Completeness check
        $requiredFields = ['title', 'description', 'price', 'brand'];
        $missingFields = [];
        $completeness = 0;

        foreach ($requiredFields as $field) {
            if (!empty($productData[$field])) {
                $completeness += 25;
            } else {
                $missingFields[] = $field;
            }
        }

        $analysis .= "1. COMPLETENESS SCORE: {$completeness}%\n";
        if (!empty($missingFields)) {
            $analysis .= "   Missing: " . implode(', ', $missingFields) . "\n";
        }

        // Basic validation checks
        $analysis .= "\n2. BASIC CHECKS:\n";

        if (!empty($productData['title'])) {
            $titleLength = strlen($productData['title']);
            if ($titleLength < 10) {
                $analysis .= "   ⚠️ Title might be too short for SEO\n";
            } elseif ($titleLength > 60) {
                $analysis .= "   ⚠️ Title might be too long for search results\n";
            } else {
                $analysis .= "   ✅ Title length is appropriate\n";
            }
        }

        if (!empty($productData['description'])) {
            $descLength = strlen($productData['description']);
            if ($descLength < 50) {
                $analysis .= "   ⚠️ Description could be more detailed\n";
            } else {
                $analysis .= "   ✅ Description has good length\n";
            }
        }

        if (!empty($productData['price'])) {
            if (is_numeric($productData['price']) && $productData['price'] > 0) {
                $analysis .= "   ✅ Price is valid\n";
            } else {
                $analysis .= "   ❌ Price format needs correction\n";
            }
        }

        if (!empty($productData['meta_seo'])) {
            $seoLength = strlen($productData['meta_seo']);
            if ($seoLength < 120 || $seoLength > 160) {
                $analysis .= "   ⚠️ SEO meta should be 120-160 characters\n";
            } else {
                $analysis .= "   ✅ SEO meta length is optimal\n";
            }
        } else {
            $analysis .= "   ⚠️ Missing SEO meta description\n";
        }

        // General recommendations
        $analysis .= "\n3. RECOMMENDATIONS:\n";
        $analysis .= "   • Ensure all product information is accurate and complete\n";
        $analysis .= "   • Use high-quality, descriptive language\n";
        $analysis .= "   • Include key features and benefits\n";
        $analysis .= "   • Optimize for search engines with relevant keywords\n";
        $analysis .= "   • Consider your target audience when writing\n";

        $analysis .= "\n4. MARKET READINESS:\n";
        if ($completeness >= 75) {
            $analysis .= "   ✅ Good - Product data is mostly complete\n";
        } elseif ($completeness >= 50) {
            $analysis .= "   ⚠️ Fair - Some important fields are missing\n";
        } else {
            $analysis .= "   ❌ Poor - Many required fields need attention\n";
        }

        $analysis .= "\nNote: This is a basic analysis. For detailed AI insights, ensure Gemini API is properly configured.";

        return $analysis;
    }

    /**
     * Robust JSON parsing for Gemini responses
     */
    private function parseJsonResponse(string $response): ?array
    {
        // Try multiple cleaning strategies
        $cleanStrategies = [
            // Original response
            $response,
            // Remove markdown code blocks
            preg_replace('/```json\s*/', '', preg_replace('/```\s*$/', '', $response)),
            // Remove any leading/trailing text and get JSON part
            preg_replace('/^[^{]*({.*})[^}]*$/s', '$1', $response),
            // Extract first JSON object found
            preg_match('/\{.*\}/s', $response, $matches) ? $matches[0] : '',
        ];

        foreach ($cleanStrategies as $attempt) {
            $cleaned = trim($attempt);
            if (empty($cleaned)) continue;

            $decoded = json_decode($cleaned, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // Validate that it has expected structure
                if (isset($decoded['category']) || isset($decoded['brand']) || isset($decoded['color']) ||
                    isset($decoded['keywords']) || count($decoded) > 0) {
                    return $decoded;
                }
            }
        }

        // Log the problematic response for debugging
        log_message('warning', 'Failed to parse Gemini JSON response: ' . substr($response, 0, 200));
        return null;
    }

    /**
     * Create smart fallback filters from query when AI fails
     */
    private function createSmartFallback(string $query): array
    {
        $query = strtolower($query);
        $filters = [];

        // Enhanced categories with Turkish support
        $categories = [
            'phone' => ['phone', 'smartphone', 'iphone', 'android', 'mobile', 'cell', 'telefon', 'akıllı telefon'],
            'electronics' => ['electronics', 'electronic', 'tech', 'device', 'elektronik', 'teknoloji'],
            'shirt' => ['shirt', 'blouse', 'top', 'tshirt', 't-shirt', 'gömlek', 'tişört'],
            'clothing' => ['clothing', 'clothes', 'wear', 'apparel', 'giyim', 'kıyafet'],
            'shoes' => ['shoes', 'sneakers', 'boots', 'footwear', 'ayakkabı', 'spor ayakkabı'],
            'dress' => ['dress', 'gown', 'elbise'],
            'jacket' => ['jacket', 'coat', 'blazer', 'ceket', 'mont'],
            'headphones' => ['headphones', 'earphones', 'earbuds', 'headset', 'kulaklık'],
            'laptop' => ['laptop', 'notebook', 'computer', 'dizüstü', 'bilgisayar'],
            'bag' => ['bag', 'backpack', 'handbag', 'çanta', 'sırt çantası'],
            'watch' => ['watch', 'smartwatch', 'saat', 'akıllı saat'],
            'glasses' => ['glasses', 'sunglasses', 'gözlük', 'güneş gözlüğü']
        ];

        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($query, $keyword) !== false) {
                    $filters['category'] = $category;
                    break 2;
                }
            }
        }

        // Enhanced colors with Turkish support
        $colors = [
            'white' => ['white', 'beyaz'],
            'black' => ['black', 'siyah'],
            'red' => ['red', 'kırmızı'],
            'blue' => ['blue', 'mavi'],
            'green' => ['green', 'yeşil'],
            'yellow' => ['yellow', 'sarı'],
            'pink' => ['pink', 'pembe'],
            'gray' => ['gray', 'grey', 'gri'],
            'brown' => ['brown', 'kahverengi'],
            'purple' => ['purple', 'mor'],
            'orange' => ['orange', 'turuncu'],
            'navy' => ['navy', 'lacivert']
        ];

        foreach ($colors as $colorEn => $colorVariants) {
            foreach ($colorVariants as $variant) {
                if (strpos($query, $variant) !== false) {
                    $filters['color'] = $colorEn;
                    break 2;
                }
            }
        }

        // Enhanced gender detection with Turkish support
        $femaleTerms = ['women', 'woman', 'female', 'kadın', 'bayan', 'kadınlar', 'kız'];
        $maleTerms = ['men', 'man', 'male', 'erkek', 'bay', 'erkekler'];

        foreach ($femaleTerms as $term) {
            if (strpos($query, $term) !== false) {
                $filters['gender'] = 'female';
                break;
            }
        }

        if (!isset($filters['gender'])) {
            foreach ($maleTerms as $term) {
                if (strpos($query, $term) !== false) {
                    $filters['gender'] = 'male';
                    break;
                }
            }
        }

        // Enhanced brands - more comprehensive
        $brands = [
            'apple', 'samsung', 'nike', 'adidas', 'sony', 'google', 'microsoft',
            'hp', 'dell', 'lenovo', 'asus', 'acer', 'lg', 'xiaomi', 'huawei',
            'oneplus', 'zara', 'h&m', 'mango', 'lcw', 'koton', 'defacto',
            'puma', 'under armour', 'new balance', 'converse', 'vans'
        ];

        foreach ($brands as $brand) {
            if (strpos($query, $brand) !== false) {
                $filters['brand'] = ucwords($brand);
                break;
            }
        }

        // Enhanced price range detection with Turkish support
        $budgetTerms = ['budget', 'cheap', 'under', 'ucuz', 'uygun', 'ekonomik', 'altında'];
        $premiumTerms = ['premium', 'expensive', 'luxury', 'pahalı', 'lüks', 'üst segment'];

        foreach ($budgetTerms as $term) {
            if (strpos($query, $term) !== false) {
                $filters['price_range'] = 'budget';
                break;
            }
        }

        if (!isset($filters['price_range'])) {
            foreach ($premiumTerms as $term) {
                if (strpos($query, $term) !== false) {
                    $filters['price_range'] = 'premium';
                    break;
                }
            }
        }

        // Style detection
        $styles = [
            'casual' => ['casual', 'günlük', 'rahat'],
            'formal' => ['formal', 'resmi', 'iş'],
            'sport' => ['sport', 'athletic', 'spor', 'sportif'],
            'elegant' => ['elegant', 'şık', 'zarif']
        ];

        foreach ($styles as $styleEn => $styleVariants) {
            foreach ($styleVariants as $variant) {
                if (strpos($query, $variant) !== false) {
                    $filters['style'] = $styleEn;
                    break 2;
                }
            }
        }

        // Always include enhanced keywords
        $filters['keywords'] = $this->extractEnhancedKeywords($query);

        return $filters;
    }

    /**
     * Enhance AI filters with additional semantic understanding
     */
    private function enhanceFiltersWithSemantics(string $query, array $filters): array
    {
        $queryLower = strtolower($query);

        // Enhanced Turkish language support
        $turkishMappings = [
            // Categories
            'telefon' => 'phone',
            'kulaklık' => 'headphones',
            'elbise' => 'dress',
            'ayakkabı' => 'shoes',
            'tişört' => 'shirt',
            'gömlek' => 'shirt',
            'pantolon' => 'pants',
            'ceket' => 'jacket',
            'bilgisayar' => 'laptop',
            'klavye' => 'keyboard',
            'mouse' => 'mouse',

            // Price ranges
            'ucuz' => 'budget',
            'uygun' => 'budget',
            'pahalı' => 'premium',
            'lüks' => 'premium',
            'orta' => 'medium',

            // Gender
            'erkek' => 'male',
            'kadın' => 'female',
            'bayan' => 'female',
            'unisex' => 'unisex',

            // Colors
            'kırmızı' => 'red',
            'mavi' => 'blue',
            'yeşil' => 'green',
            'sarı' => 'yellow',
            'siyah' => 'black',
            'beyaz' => 'white',
            'gri' => 'gray',
            'pembe' => 'pink',

            // Styles
            'spor' => 'sport',
            'resmi' => 'formal',
            'günlük' => 'casual',
            'profesyonel' => 'professional'
        ];

        // Apply Turkish mappings
        foreach ($turkishMappings as $turkish => $english) {
            if (strpos($queryLower, $turkish) !== false) {
                // Map to appropriate filter category
                if (in_array($english, ['phone', 'headphones', 'dress', 'shoes', 'shirt', 'pants', 'jacket', 'laptop', 'keyboard', 'mouse'])) {
                    if (empty($filters['category'])) {
                        $filters['category'] = $english;
                    }
                } elseif (in_array($english, ['budget', 'medium', 'premium'])) {
                    if (empty($filters['price_range'])) {
                        $filters['price_range'] = $english;
                    }
                } elseif (in_array($english, ['male', 'female', 'unisex'])) {
                    if (empty($filters['gender'])) {
                        $filters['gender'] = $english;
                    }
                } elseif (in_array($english, ['red', 'blue', 'green', 'yellow', 'black', 'white', 'gray', 'pink'])) {
                    if (empty($filters['color'])) {
                        $filters['color'] = $english;
                    }
                } elseif (in_array($english, ['sport', 'formal', 'casual', 'professional'])) {
                    if (empty($filters['style'])) {
                        $filters['style'] = $english;
                    }
                }
            }
        }

        // Enhance keywords with synonyms
        if (!isset($filters['keywords'])) {
            $filters['keywords'] = [];
        }

        // Add original query words as keywords
        $queryWords = explode(' ', $queryLower);
        $stopWords = ['için', 'ile', 'and', 'or', 'the', 'a', 'an', 'in', 'on', 'at', 've', 'veya'];

        foreach ($queryWords as $word) {
            $word = trim($word);
            if (strlen($word) > 2 && !in_array($word, $stopWords) && !in_array($word, $filters['keywords'])) {
                $filters['keywords'][] = $word;
            }
        }

        // Add brand detection for common variations
        $brandVariations = [
            'apple' => ['apple', 'iphone', 'ipad', 'macbook'],
            'samsung' => ['samsung', 'galaxy'],
            'google' => ['google', 'pixel'],
            'microsoft' => ['microsoft', 'surface', 'xbox'],
            'sony' => ['sony', 'playstation', 'xperia'],
            'nike' => ['nike'],
            'adidas' => ['adidas'],
            'zara' => ['zara'],
            'h&m' => ['h&m', 'hm']
        ];

        foreach ($brandVariations as $brand => $variations) {
            foreach ($variations as $variation) {
                if (strpos($queryLower, $variation) !== false) {
                    if (empty($filters['brand'])) {
                        $filters['brand'] = ucfirst($brand);
                    }
                    break;
                }
            }
        }

        return $filters;
    }
}
