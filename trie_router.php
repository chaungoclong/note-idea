<?php

declare(strict_types=1);

class TrieNode
{
    public array $children = [];
    public bool $isEnd = false;
    public array $handlers = [];
    public ?string $paramName = null;
}

class TrieRouter
{
    private TrieNode $root;
    private string $paramPattern = '/:([^\/]+)/';

    public const STATUS_OK = 200;
    public const STATUS_NOT_FOUND = 404;
    public const STATUS_METHOD_NOT_ALLOWED = 405;

    public function __construct()
    {
        $this->root = new TrieNode();
    }

    public function addRoute(string $method, string $path, callable $handler): void
    {
        $node = $this->root;
        $parts = explode('/', trim($path, '/'));

        foreach ($parts as $part) {
            if (preg_match($this->paramPattern, $part)) {
                $paramName = substr($part, 1);
                $part = ":param";
            }

            if (!isset($node->children[$part])) {
                $node->children[$part] = new TrieNode();
            }
            $node = $node->children[$part];

            if (isset($paramName)) {
                $node->paramName = $paramName;
                unset($paramName);
            }
        }

        $node->isEnd = true;
        $node->handlers[$method] = $handler;
    }

    /** @return array{status: int, handler: ?callable, params: array<string, string>, message: string, allowed_methods?: array<string>} */
    public function match(string $method, string $path): array
    {
        $node = $this->root;
        $params = [];
        $parts = explode('/', trim($path, '/'));

        foreach ($parts as $part) {
            if (isset($node->children[$part])) {
                $node = $node->children[$part];
            } elseif (isset($node->children[':param'])) {
                $node = $node->children[':param'];
                if ($node->paramName !== null) {
                    $params[$node->paramName] = $part;
                }
            } else {
                return [
                    'status' => self::STATUS_NOT_FOUND,
                    'handler' => null,
                    'params' => [],
                    'message' => "Route '$path' not found"
                ];
            }
        }

        if (!$node->isEnd) {
            return [
                'status' => self::STATUS_NOT_FOUND,
                'handler' => null,
                'params' => [],
                'message' => "Route '$path' not found"
            ];
        }

        if (!isset($node->handlers[$method])) {
            return [
                'status' => self::STATUS_METHOD_NOT_ALLOWED,
                'handler' => null,
                'params' => $params,
                'message' => "Method '$method' not allowed for route '$path'",
                'allowed_methods' => array_keys($node->handlers)
            ];
        }

        return [
            'status' => self::STATUS_OK,
            'handler' => $node->handlers[$method],
            'params' => $params,
            'message' => 'Route matched successfully'
        ];
    }

    public function get(string $path, callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }
}

// Ví dụ sử dụng
$router = new TrieRouter();

$router->get('/', fn(): string => "Home Page");

$router->get('/user/:id', fn(array $params): string => "User ID: " . $params['id']);

$router->get(
    '/user/:id/post/:postId',
    fn(array $params): string => "User ID: " . $params['id'] . ", Post ID: " . $params['postId']
);

$router->post('/user/:id', fn(array $params): string => "POST User ID: " . $params['id']);

// Test router
$testCases = [
    ['GET', '/'],
    ['GET', '/user/123'],
    ['GET', '/user/123/post/456'],
    ['POST', '/user/789'],
    ['POST', '/'],
    ['GET', '/not/found']
];

foreach ($testCases as [$method, $path]) {
    $result = $router->match($method, $path);

    echo "Method: $method, Path: $path\n";
    echo "Status: " . $result['status'] . "\n";
    echo "Message: " . $result['message'] . "\n";

    if ($result['status'] === TrieRouter::STATUS_OK) {
        $output = $result['params']
            ? $result['handler']($result['params'])
            : $result['handler']();
        echo "Result: $output\n";
    } elseif ($result['status'] === TrieRouter::STATUS_METHOD_NOT_ALLOWED) {
        echo "Allowed Methods: " . implode(', ', $result['allowed_methods']) . "\n";
    }

    echo "----------------\n";
}