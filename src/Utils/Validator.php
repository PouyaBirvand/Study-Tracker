<?php
/**
 * Validation Helper
 * کلاس کمکی اعتبارسنجی
 */
class Validator 
{
    private array $data;
    private array $errors = [];
    
    public function __construct(array $data) 
    {
        $this->data = $data;
    }
    
    /**
     * اعتبارسنجی فیلدها
     */
    public function validate(array $rules): bool 
    {
        foreach ($rules as $field => $rule) {
            $this->validateField($field, $rule);
        }
        
        return empty($this->errors);
    }
    
    /**
     * دریافت خطاها
     */
    public function getErrors(): array 
    {
        return $this->errors;
    }
    
    /**
     * دریافت اولین خطا
     */
    public function getFirstError(): ?string 
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
    
    /**
     * اعتبارسنجی یک فیلد
     */
    private function validateField(string $field, string $rules): void 
    {
        $value = $this->data[$field] ?? null;
        $rulesList = explode('|', $rules);
        
        foreach ($rulesList as $rule) {
            $this->applyRule($field, $value, $rule);
        }
    }
    
    /**
     * اعمال قانون اعتبارسنجی
     */
    private function applyRule(string $field, $value, string $rule): void 
    {
        if (strpos($rule, ':') !== false) {
            [$ruleName, $parameter] = explode(':', $rule, 2);
        } else {
            $ruleName = $rule;
            $parameter = null;
        }
        
        switch ($ruleName) {
            case 'required':
                if (empty($value)) {
                    $this->addError($field, "فیلد {$field} الزامی است");
                }
                break;
                
            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "فرمت ایمیل نامعتبر است");
                }
                break;
                
            case 'min':
                if ($value && strlen($value) < (int)$parameter) {
                    $this->addError($field, "فیلد {$field} باید حداقل {$parameter} کاراکتر باشد");
                }
                break;
                
            case 'max':
                if ($value && strlen($value) > (int)$parameter) {
                    $this->addError($field, "فیلد {$field} باید حداکثر {$parameter} کاراکتر باشد");
                }
                break;
                
            case 'numeric':
                if ($value && !is_numeric($value)) {
                    $this->addError($field, "فیلد {$field} باید عددی باشد");
                }
                break;
                
            case 'integer':
                if ($value && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addError($field, "فیلد {$field} باید عدد صحیح باشد");
                }
                break;
                
            case 'in':
                $allowedValues = explode(',', $parameter);
                if ($value && !in_array($value, $allowedValues)) {
                    $this->addError($field, "مقدار فیلد {$field} نامعتبر است");
                }
                break;
                
            case 'unique':
                if ($value && $this->checkUnique($parameter, $field, $value)) {
                    $this->addError($field, "این {$field} قبلاً استفاده شده است");
                }
                break;
        }
    }
    
    /**
     * اضافه کردن خطا
     */
    private function addError(string $field, string $message): void 
    {
        $this->errors[$field] = $message;
    }
    
    /**
     * بررسی یکتا بودن
     */
    private function checkUnique(string $table, string $field, $value): bool 
    {
        $db = DatabaseConfig::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE {$field} = ? AND deleted_at IS NULL");
        $stmt->execute([$value]);
        
        return $stmt->fetchColumn() > 0;
    }
}
