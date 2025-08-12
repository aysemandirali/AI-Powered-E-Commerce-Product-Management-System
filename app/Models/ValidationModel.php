<?php
namespace App\Models;
use CodeIgniter\Model;

class ValidationModel extends Model
{
    protected $table = 'product_validations';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'product_id', 'field_name', 'is_valid',
        'validation_message', 'validated_by', 'validated_at'
    ];

    // Save field validation result (mock implementation for testing)
    public function saveValidation($productId, $fieldName, $isValid, $message = null, $validatedBy = null)
    {
        // For testing without database, just log the validation
        log_message('info', "Validation saved: Product {$productId}, Field {$fieldName}, Valid: " . ($isValid ? 'Yes' : 'No'));
        return true; // Always return success for testing
    }

    // Get validations for a product
    public function getProductValidations($productId)
    {
        return $this->where('product_id', $productId)
                    ->orderBy('validated_at', 'DESC')
                    ->findAll();
    }

    // Get latest validation for a specific field
    public function getLatestFieldValidation($productId, $fieldName)
    {
        return $this->where('product_id', $productId)
                    ->where('field_name', $fieldName)
                    ->orderBy('validated_at', 'DESC')
                    ->first();
    }

    // Get validation summary for a product
    public function getValidationSummary($productId)
    {
        $validations = $this->select('field_name, is_valid, validation_message, validated_at')
                            ->where('product_id', $productId)
                            ->groupStart()
                                ->whereIn('field_name', ['title', 'description', 'price', 'category_id'])
                            ->groupEnd()
                            ->orderBy('validated_at', 'DESC')
                            ->findAll();

        $summary = [];
        $processedFields = [];

        foreach ($validations as $validation) {
            if (!in_array($validation['field_name'], $processedFields)) {
                $summary[$validation['field_name']] = [
                    'is_valid' => $validation['is_valid'],
                    'message' => $validation['validation_message'],
                    'validated_at' => $validation['validated_at']
                ];
                $processedFields[] = $validation['field_name'];
            }
        }

        return $summary;
    }

    // Check if all required fields are valid
    public function areAllFieldsValid($productId)
    {
        $requiredFields = ['title', 'description', 'price', 'category_id'];
        $summary = $this->getValidationSummary($productId);

        foreach ($requiredFields as $field) {
            if (!isset($summary[$field]) || !$summary[$field]['is_valid']) {
                return false;
            }
        }

        return true;
    }

    // Delete old validations (keep only latest 10 per field)
    public function cleanOldValidations($productId)
    {
        $fields = ['title', 'description', 'price', 'category_id'];
        
        foreach ($fields as $field) {
            $validations = $this->where('product_id', $productId)
                                ->where('field_name', $field)
                                ->orderBy('validated_at', 'DESC')
                                ->findAll();
            
            if (count($validations) > 10) {
                $toDelete = array_slice($validations, 10);
                foreach ($toDelete as $validation) {
                    $this->delete($validation['id']);
                }
            }
        }
    }
}