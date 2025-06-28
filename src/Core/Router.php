<?php
/**
 * Router Class
 * سیستم مسیریابی
 */
class Router 
{
    private array $routes = [];
    private array $middlewares = [];
    
    /**
     * تعریف مسیر GET
     */
    public function get(string $path, string $handler, array $middlewares = []): void 
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }
    
    /**
     * تعریف مسیر POST
     */
    public function post(string $path, string $handler, array $middlewares = []): void 
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }
    
    /**
     * تعریف مسیر PUT
     */
    public function put(string $path, string $handler, array $middlewares = []): void 
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }
    
    /**
     * تعریف مسیر DELETE
     */
    public function delete(string $path, string $handler, array $middlewares = []): void 
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }
    
    /**
     * اضافه کردن مسیر
     */
    private function addRoute(string $method, string $path, string $handler, array $middlewares): void 
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares,
            'pattern' => $this->convertToPattern($path)
        ];
    }
    
    /**
     * تبدیل مسیر به الگوی regex
     */
    private function convertToPattern(string $path): string 
    {
        // تبدیل {id} به (\d+) و {slug} به ([a-zA-Z0-9-_]+)
        $pattern = preg_replace('/\{(\w+)\}/', '([a-zA-Z0-9-_]+)', $path);
        return '#^' . $pattern . '$#';
    }
    
    /**
     * اجرای مسیریاب
     */
    public function dispatch(): void 
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // حذف پیشوند API
        $path = preg_replace('#^/api#', '', $path);
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $path, $matches)) {
                // استخراج پارامترها
                array_shift($matches); // حذف match کامل
                $this->setRouteParams($matches, $route['path']);
                
                // اجرای middlewares
                $this->runMiddlewares($route['middlewares']);
                
                // اجرای handler
                $this->callHandler($route['handler']);
                return;
            }
        }
        
        // مسیر یافت نشد
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'مسیر یافت نشد'
        ], JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * تنظیم پارامترهای مسیر
     */
    private function setRouteParams(array $matches, string $path): void 
    {
        preg_match_all('/\{(\w+)\}/', $path, $paramNames);
        
        foreach ($paramNames[1] as $index => $paramName) {
            if (isset($matches[$index])) {
                $_GET[$paramName] = $matches[$index];
            }
        }
    }
    
    /**
     * اجرای middlewares
     */
    private function runMiddlewares(array $middlewares): void 
    {
        foreach ($middlewares as $middleware) {
            if (class_exists($middleware)) {
                $middlewareInstance = new $middleware();
                $middlewareInstance->handle();
            }
        }
    }
    
    /**
     * فراخوانی handler
     */
    private function callHandler(string $handler): void 
    {
        [$controllerName, $methodName] = explode('@', $handler);
        
        if (!class_exists($controllerName)) {
            throw new Exception("کنترلر {$controllerName} یافت نشد");
        }
        
        $controller = new $controllerName();
        
        if (!method_exists($controller, $methodName)) {
            throw new Exception("متد {$methodName} در کنترلر {$controllerName} یافت نشد");
        }
        
        $controller->$methodName();
    }
}
