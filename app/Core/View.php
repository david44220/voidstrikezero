<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

class View
{
    private static string $viewsPath = '';
    private static array $sections = [];
    private static array $sectionStack = [];

    public static function init(string $viewsPath): void
    {
        self::$viewsPath = rtrim($viewsPath, '/\\');
    }

    public static function render(string $view, array $data = [], ?string $layout = 'layouts/main'): string
    {
        $viewPath = self::resolvePath($view);
        if (!file_exists($viewPath)) {
            throw new RuntimeException("View [{$view}] not found at {$viewPath}");
        }

        // Render child view
        extract($data, EXTR_SKIP);
        ob_start();
        include $viewPath;
        $content = ob_get_clean();

        // Render layout if specified
        if ($layout !== null) {
            $layoutPath = self::resolvePath($layout);
            if (!file_exists($layoutPath)) {
                throw new RuntimeException("Layout [{$layout}] not found at {$layoutPath}");
            }

            $slot = $content;
            ob_start();
            include $layoutPath;
            return ob_get_clean();
        }

        return $content;
    }

    public static function include(string $view, array $data = []): void
    {
        $path = self::resolvePath($view);
        if (!file_exists($path)) {
            throw new RuntimeException("Partial view [{$view}] not found at {$path}");
        }

        extract($data, EXTR_SKIP);
        include $path;
    }

    public static function startSection(string $name): void
    {
        self::$sectionStack[] = $name;
        ob_start();
    }

    public static function endSection(): void
    {
        $name = array_pop(self::$sectionStack);
        if ($name) {
            self::$sections[$name] = ob_get_clean();
        }
    }

    public static function yieldSection(string $name, string $default = ''): string
    {
        return self::$sections[$name] ?? $default;
    }

    private static function resolvePath(string $view): string
    {
        $normalized = str_replace('.', DIRECTORY_SEPARATOR, $view);
        $normalized = str_replace('/', DIRECTORY_SEPARATOR, $normalized);
        return self::$viewsPath . DIRECTORY_SEPARATOR . $normalized . '.php';
    }
}
