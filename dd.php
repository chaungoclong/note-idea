<?php

require_once __DIR__ . '/vendor/autoload.php';

function dd(...$variables)
{
    // Kiểm tra nếu đang chạy trong CLI
    if (PHP_SAPI === 'cli') {
        foreach ($variables as $variable) {
            echo formatVariableCLI($variable, 0) . "\n\n";
        }
    } else {
        echo '<!DOCTYPE html>
        <html lang="vi">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Dump and Die - Laravel Style</title>
            <style>
                body { font-family: Consolas, monospace; background-color: #f4f4f4; padding: 20px; }
                .dd-container { background: #282c34; color: #ffffff; padding: 20px; border-radius: 10px; max-width: 800px; margin: auto; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
                .dd-key { color: #61afef; }
                .dd-string { color: #98c379; }
                .dd-number { color: #d19a66; }
                .dd-boolean { color: #e06c75; }
                .dd-null { color: #c678dd; }
                .dd-object, .dd-array { margin-left: 20px; border-left: 2px solid #555; padding-left: 10px; display: none; }
                .toggle-btn { cursor: pointer; color: #e5c07b; font-weight: bold; }
                .toggle-btn::before { content: "▶ "; display: inline-block; transition: transform 0.2s; }
                .expanded::before { transform: rotate(90deg); }
                .dd-class { color: #56b6c2; font-weight: bold; }
                .dd-id { color: #e06c75; }
            </style>
            <script>
                function toggleExpand(element) {
                    let next = element.nextElementSibling;
                    if (next.style.display === "none" || next.style.display === "") {
                        next.style.display = "block";
                        element.classList.add("expanded");
                    } else {
                        next.style.display = "none";
                        element.classList.remove("expanded");
                    }
                }
            </script>
        </head>
        <body>
            <div class="dd-container">';

        foreach ($variables as $variable) {
            echo formatVariable($variable) . "<hr>";
        }

        echo '</div></body></html>';
    }
    exit();
}

function formatVariable($data): string
{
    if (is_null($data)) {
        return '<span class="dd-null">null</span>';
    }
    if (is_string($data)) {
        return '<span class="dd-string">"' . htmlspecialchars($data) . '"</span>';
    }
    if (is_numeric($data)) {
        return '<span class="dd-number">' . $data . '</span>';
    }
    if (is_bool($data)) {
        return '<span class="dd-boolean">' . ($data ? 'true' : 'false') . '</span>';
    }

    if (is_array($data)) {
        $html = '<span class="toggle-btn" onclick="toggleExpand(this)">[Array] (' . count($data) . ')</span>';
        $html .= '<div class="dd-array">';
        foreach ($data as $key => $value) {
            $html .= '<div><span class="dd-key">' . htmlspecialchars($key) . ':</span> ' . formatVariable(
                    $value
                ) . '</div>';
        }
        return $html . '</div>';
    }

    if (is_object($data)) {
        $className = get_class($data);

        $properties = getObjectProperties($data);

        $html = '<span class="toggle-btn" onclick="toggleExpand(this)">
                [Object] <span class="dd-class">' . $className . '@' . spl_object_id($data) . '</span> (' . count(
                $properties
            ) . ')
                </span>';
        $html .= '<div class="dd-object">';
        foreach ($properties as $key => $value) {
            $html .= '<div><span class="dd-key">' . htmlspecialchars($key) . ':</span> ' . formatVariable(
                    $value
                ) . '</div>';
        }
        return $html . '</div>';
    }

    return '<span>' . htmlspecialchars(print_r($data, true)) . '</span>';
}

function getObjectProperties($object): array
{
    $reflection = new ReflectionObject($object);
    $properties = [];

    foreach ($reflection->getProperties() as $prop) {
        $prop->setAccessible(true);
        $properties[$prop->getName()] = $prop->getValue($object);
    }

    $arrayCast = (array)$object;
    $castProperties = [];
    foreach ($arrayCast as $castKey => $castValue) {
        $cleanKey = $castKey;
        if (strpos($castKey, "\0") !== false) {
            $parts = explode("\0", $castKey);
            $cleanKey = end($parts);
        }
        $castProperties[$cleanKey] = $castValue;
    }

    return array_merge($castProperties, $properties);
}

function formatVariableCLI($data, $indent = 0): string
{
    // Mã ANSI màu sắc
    $colors = [
        'null' => "\033[35m",  // Magenta for null
        'string' => "\033[32m", // Green for strings
        'number' => "\033[33m", // Yellow for numbers
        'boolean' => "\033[31m", // Red for booleans
        'reset' => "\033[0m",   // Reset color
        'key' => "\033[36m",    // Cyan for keys
        'class' => "\033[34m",  // Blue for classes
    ];

    $spaces = str_repeat('  ', $indent);

    if (is_null($data)) {
        return $spaces . $colors['null'] . '[NULL]' . $colors['reset'];
    }
    if (is_string($data)) {
        return $spaces . $colors['string'] . '"' . $data . '"' . $colors['reset'];
    }
    if (is_numeric($data)) {
        return $spaces . $colors['number'] . $data . $colors['reset'];
    }
    if (is_bool($data)) {
        return $spaces . $colors['boolean'] . ($data ? 'true' : 'false') . $colors['reset'];
    }

    if (is_array($data)) {
        $output = $spaces . $colors['key'] . "[Array] (" . count($data) . " items)" . $colors['reset'] . "\n";
        foreach ($data as $key => $value) {
            $output .= $spaces . '  ' . $colors['key'] . $key . $colors['reset']
                . " => " . formatVariableCLI($value, $indent + 1) . "\n";
        }
        return $output;
    }

    if (is_object($data)) {
        $className = get_class($data);
        $output = $spaces . $colors['class'] . "[Object] {$className}" . $colors['reset'] . "\n";

        $properties = getObjectProperties($data);

        foreach ($properties as $key => $value) {
            $output .= $spaces . '  ' . $colors['key'] . $key . $colors['reset'] . " => " . formatVariableCLI(
                    $value,
                    $indent + 1
                ) . "\n";
        }
        return $output;
    }

    return $spaces . print_r($data, true);
}

// 🛠️ **Ví dụ sử dụng**
class User
{
    private $id;
    public $name;
    public $email;
    public $role;

    public function __construct($id, $name, $email, $role)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->role = $role;
    }
}

$user1 = new User(101, "John Doe", "john@example.com", "Admin");
$user2 = new User(102, "Jane Smith", "jane@example.com", "Editor");

$settings = [
    "theme" => "dark",
    "notifications" => true,
    "language" => "English",
    "meta" => ["version" => "1.2.3", "lastUpdate" => "2025-04-02"]
];

// **Dump nhiều biến**
dd(
    "Hello World!",
    12345,
    true,
    null,
    [1, 2, "test", false],
    ["key1" => "value1", "key2" => 42, "nested" => ["a" => "A", "b" => [10, 20, 30]]],
    $user1,
    $user2,
    $settings,
    new DateTime(),
);