<?php

/**
 * Base Controller - Surat Digital
 */

class Controller
{
    protected array $data = [];
    protected string $layout = 'layouts/main';
    protected array $jsFiles = [];
    protected array $cssFiles = [];

    public function __construct()
    {
    }

    protected function view(string $view, array $data = []): void
    {
        $this->data = array_merge($this->data, $data);
        extract($this->data);
        
        ob_start();
        
        $viewPath = VIEWS_PATH . 'pages/' . $view . '.php';
        if (!file_exists($viewPath)) {
            $viewPath = VIEWS_PATH . $view . '.php';
        }
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            throw new Exception("View not found: {$view}");
        }
        
        $content = ob_get_clean();
        
        $layoutPath = VIEWS_PATH . $this->layout . '.php';
        if (file_exists($layoutPath)) {
            require $layoutPath;
        } else {
            echo $content;
        }
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function success(mixed $data = null, string $message = 'Berhasil', int $code = 200): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    protected function error(string $message = 'Terjadi kesalahan', int $code = 400, mixed $errors = null): void
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        $this->json($response, $code);
    }

    protected function redirect(string $url): void
    {
        if (str_starts_with($url, '/') && !str_starts_with($url, 'http')) {
            $basePath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
            if ($basePath !== '/' && $basePath !== '\\') {
                $normalizedBasePath = str_replace('\\', '/', $basePath);
                if (str_starts_with($url, $normalizedBasePath . '/') || $url === $normalizedBasePath) {
                    $url = substr($url, strlen($normalizedBasePath));
                }
            }
            $url = BASE_URL . '/' . ltrim($url, '/');
        }
        header('Location: ' . $url);
        exit;
    }

    protected function redirectBack(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/dashboard';
        $this->redirect($referer);
    }

    protected function redirectWith(string $url, string $type, string $message): void
    {
        Session::setFlash($type, $message);
        $this->redirect($url);
    }

    protected function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST' || 
               (isset($_POST['_method']) && $_POST['_method'] === 'POST');
    }

    protected function getPost(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }
}
