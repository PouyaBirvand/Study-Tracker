<?php
/**
 * Simple Test Framework
 * فریمورک ساده تست
 */
class TestFramework 
{
    private int $passed = 0;
    private int $failed = 0;
    private array $failures = [];
    
    public function describe(string $description, callable $tests): void 
    {
        echo "\n🧪 {$description}\n";
        echo str_repeat('-', 50) . "\n";
        
        $tests();
        
        echo "\n";
    }
    
    public function it(string $description, callable $test): void 
    {
        try {
            $test();
            $this->passed++;
            echo "✅ {$description}\n";
        } catch (Exception $e) {
            $this->failed++;
            $this->failures[] = [
                'description' => $description,
                'error' => $e->getMessage()
            ];
            echo "❌ {$description}\n";
            echo "   Error: {$e->getMessage()}\n";
        }
    }
    
    public function expect($actual): Expectation 
    {
        return new Expectation($actual);
    }
    
    public function summary(): void 
    {
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "📊 خلاصه تست‌ها:\n";
        echo "✅ موفق: {$this->passed}\n";
        echo "❌ ناموفق: {$this->failed}\n";
        echo "📈 درصد موفقیت: " . round(($this->passed / ($this->passed + $this->failed)) * 100, 2) . "%\n";
        
        if (!empty($this->failures)) {
            echo "\n🔍 جزئیات خطاها:\n";
            foreach ($this->failures as $failure) {
                echo "- {$failure['description']}: {$failure['error']}\n";
            }
        }
        
        echo str_repeat('=', 50) . "\n";
    }
}

class Expectation 
{
    private $actual;
    
    public function __construct($actual) 
    {
        $this->actual = $actual;
    }
    
    public function toBe($expected): void 
    {
        if ($this->actual !== $expected) {
            throw new Exception("Expected '{$expected}' but got '{$this->actual}'");
        }
    }
    
    public function toEqual($expected): void 
    {
        if ($this->actual != $expected) {
            throw new Exception("Expected '{$expected}' but got '{$this->actual}'");
        }
    }
    
    public function toBeTrue(): void 
    {
        if ($this->actual !== true) {
            throw new Exception("Expected true but got " . var_export($this->actual, true));
        }
    }
    
    public function toBeFalse(): void 
    {
        if ($this->actual !== false) {
            throw new Exception("Expected false but got " . var_export($this->actual, true));
        }
    }
    
    public function toBeNull(): void 
    {
        if ($this->actual !== null) {
            throw new Exception("Expected null but got " . var_export($this->actual, true));
        }
    }
    
    public function toContain($needle): void 
    {
        if (is_array($this->actual)) {
            if (!in_array($needle, $this->actual)) {
                throw new Exception("Expected array to contain '{$needle}'");
            }
        } elseif (is_string($this->actual)) {
            if (strpos($this->actual, $needle) === false) {
                throw new Exception("Expected string to contain '{$needle}'");
            }
        } else {
            throw new Exception("toContain can only be used with arrays or strings");
        }
    }
    
    public function toHaveLength($length): void 
    {
        $actualLength = is_array($this->actual) ? count($this->actual) : strlen($this->actual);
        if ($actualLength !== $length) {
            throw new Exception("Expected length {$length} but got {$actualLength}");
        }
    }
}

