<?php
/**
 * Input Validation Utility
 * ابزار اعتبارسنجی ورودی‌ها
 */
class Validator 
{
    private array $errors = [];
    private array $data = [];
    
    public function __construct(array $data) 
    {
        $this->data = $data;
    }
    
    public function required(string $field, string $message = null): self 
    {
        if (!isset($this->data[$field]) || empty(trim($this->data[$field]))) {
            $this->errors[$field] = $message ?? "فیلد {$field} الزامی است";
        }
        return $this;
    }
    
    public function email(string $field, string $message = null): self 
    {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?? "فرمت ایمیل نامعتبر است";
        }
        return $this;
    }
    
    public function minLength(string $field, int $min, string $message = null): self 
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $min) {
            $this->errors[$field] = $message ?? "حداقل {$min} کاراکتر مجاز است";
        }
        return $this;
    }
    
    public function maxLength(string $field, int $max, string $message = null): self 
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $max) {
            $this->errors[$field] = $message ?? "حداکثر {$max} کاراکتر مجاز است";
        }
        return $this;
    }
    
    public function numeric(string $field, string $message = null): self 
    {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = $message ?? "فیلد {$field} باید عدد باشد";
        }
        return $this;
    }
    
    public function in(string $field, array $values, string $message = null): self 
    {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $values)) {
            $this->errors[$field] = $message ?? "مقدار انتخابی نامعتبر است";
        }
        return $this;
    }
    
    public function isValid(): bool 
    {
        return empty($this->errors);
    }
    
    public function getErrors(): array 
    {
        return $this->errors;
    }
    
    public function getFirstError(): ?string 
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
}
