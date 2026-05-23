<?php

namespace App\Middleware;

class Json
{
    public static function respond(mixed $data, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function error(string $message, int $statusCode = 400, array $extra = []): never
    {
        self::respond(array_merge(['error' => $message], $extra), $statusCode);
    }

    public static function parseBody(): array
    {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return [];
        }
        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            self::error('Invalid JSON body: ' . json_last_error_msg(), 400);
        }
        return $data ?? [];
    }

    public static function requiredFields(array $data, array $fields): void
    {
        $missing = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $missing[] = $field;
            }
        }
        if (!empty($missing)) {
            self::error(
                'Missing required fields: ' . implode(', ', $missing),
                422,
                ['missing_fields' => $missing]
            );
        }
    }
}
