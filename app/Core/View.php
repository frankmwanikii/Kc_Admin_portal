<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\SettingsService;

class View
{
    public static function render(string $template, array $data = [], ?string $layout = 'layouts/app'): void
    {
        extract($data);
        $viewsPath = dirname(__DIR__, 2) . '/views';

        ob_start();
        require $viewsPath . '/' . $template . '.php';
        $content = ob_get_clean();

        if ($layout === 'layouts/admin' && self::wantsAdminNavPartial()) {
            $pageTitle = (string) ($title ?? 'Church MIS');
            $church = SettingsService::churchName();
            [$content, $styles, $scripts] = self::extractAdminNavAssets(
                $content,
                array_map('strval', $pageStyles ?? []),
                array_map('strval', $pageScripts ?? []),
            );
            self::json([
                'ok' => true,
                'title' => $pageTitle,
                'documentTitle' => $pageTitle . ' — ' . $church,
                'html' => $content,
                'styles' => $styles,
                'scripts' => $scripts,
                'url' => (string) ($_SERVER['REQUEST_URI'] ?? ''),
            ]);
        }

        if ($layout) {
            require $viewsPath . '/' . $layout . '.php';
        } else {
            echo $content;
        }
    }

    public static function wantsAdminNavPartial(): bool
    {
        return strtolower((string) ($_SERVER['HTTP_X_ADMIN_NAV'] ?? '')) === '1';
    }

    /**
     * Pull stylesheet/script URLs out of view HTML so AJAX nav can load them
     * before swapping content. DOMParser otherwise moves <link> tags into <head>
     * and they never appear inside the main fragment.
     *
     * @param list<string> $styles
     * @param list<string> $scripts
     * @return array{0: string, 1: list<string>, 2: list<string>}
     */
    private static function extractAdminNavAssets(string $content, array $styles, array $scripts): array
    {
        if (preg_match_all('/<link\b[^>]*\brel=["\']stylesheet["\'][^>]*>/i', $content, $linkMatches)) {
            foreach ($linkMatches[0] as $tag) {
                if (preg_match('/\bhref=["\']([^"\']+)["\']/i', $tag, $hrefMatch)) {
                    $styles[] = html_entity_decode($hrefMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
            }
            $content = (string) preg_replace('/<link\b[^>]*\brel=["\']stylesheet["\'][^>]*>\s*/i', '', $content);
        }

        // Also catch rel=stylesheet when attributes are in a different order.
        if (preg_match_all('/<link\b[^>]*\bhref=["\']([^"\']+)["\'][^>]*>/i', $content, $hrefOnlyMatches, PREG_SET_ORDER)) {
            foreach ($hrefOnlyMatches as $match) {
                if (stripos($match[0], 'stylesheet') === false) {
                    continue;
                }
                $styles[] = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $content = str_replace($match[0], '', $content);
            }
        }

        if (preg_match_all('/<script\b[^>]*\bsrc=["\']([^"\']+)["\'][^>]*>\s*<\/script>/i', $content, $scriptMatches, PREG_SET_ORDER)) {
            foreach ($scriptMatches as $match) {
                $scripts[] = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        return [
            $content,
            array_values(array_unique(array_filter($styles))),
            array_values(array_unique(array_filter($scripts))),
        ];
    }

    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function wantsJson(): bool
    {
        $xhr = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        if ($xhr === 'xmlhttprequest') {
            return true;
        }
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');

        return str_contains($accept, 'application/json');
    }

    public static function redirect(string $url): void
    {
        // AJAX sidebar nav should follow redirects in the browser, not as HTML.
        if (self::wantsAdminNavPartial()) {
            self::json([
                'ok' => false,
                'redirect' => $url,
            ], 200);
        }

        header('Location: ' . $url);
        exit;
    }
}
