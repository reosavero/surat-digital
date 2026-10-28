<?php

/**
 * Global Functions Helper - Surat Digital
 */

if (!function_exists('url')) {
    function url(string $path = ''): string {
        return BASE_URL . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path = ''): string {
        return BASE_URL . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('e')) {
    function e(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('getImgUrl')) {
    function getImgUrl(?string $path): string {
        if (empty($path)) return '';
        if (str_starts_with($path, 'data:') || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        $cleanPath = '/' . ltrim($path, '/');
        if (str_starts_with($cleanPath, '/uploads/')) {
            if (file_exists(BASE_PATH . 'public' . $cleanPath)) {
                return BASE_URL . '/public' . $cleanPath;
            }
        }
        return BASE_URL . $cleanPath;
    }
}

if (!function_exists('getPdfImgSrc')) {
    function getPdfImgSrc(?string $path): string {
        if (empty($path)) return '';
        if (str_starts_with($path, 'data:image')) {
            return $path;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $ctx = stream_context_create(['http' => ['timeout' => 4]]);
            $content = @file_get_contents($path, false, $ctx);
            if ($content) {
                $ext = strtolower(pathinfo(parse_url($path, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                $mime = match ($ext) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'svg' => 'image/svg+xml',
                    'gif' => 'image/gif',
                    default => 'image/png'
                };
                return 'data:' . $mime . ';base64,' . base64_encode($content);
            }
            return $path;
        }

        $rawPath = parse_url($path, PHP_URL_PATH) ?? $path;
        $cleanPath = '/' . ltrim($rawPath, '/');
        if (defined('BASE_URL')) {
            $basePath = parse_url(BASE_URL, PHP_URL_PATH) ?? '';
            if (!empty($basePath) && str_starts_with($cleanPath, $basePath)) {
                $cleanPath = substr($cleanPath, strlen($basePath));
            }
        }

        $localPath = BASE_PATH . 'public' . $cleanPath;
        if (!file_exists($localPath)) {
            $localPath = BASE_PATH . ltrim($cleanPath, '/');
        }

        if (file_exists($localPath) && !is_dir($localPath)) {
            $ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'svg' => 'image/svg+xml',
                'gif' => 'image/gif',
                default => 'image/png'
            };
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($localPath));
        }

        return getImgUrl($path);
    }
}

if (!function_exists('getPdfPageCount')) {
    function getPdfPageCount(?string $pdfPath): int {
        if (empty($pdfPath)) return 1;
        
        $cleanPath = '/' . ltrim($pdfPath, '/');
        $fullPath = BASE_PATH . 'public' . $cleanPath;
        if (!file_exists($fullPath)) {
            $fullPath = BASE_PATH . ltrim($cleanPath, '/');
        }
        if (!file_exists($fullPath)) return 1;
        
        $content = @file_get_contents($fullPath);
        if (!$content) return 1;

        if (preg_match_all('/\/Count\s+(\d+)/i', $content, $m)) {
            $max = 1;
            foreach ($m[1] as $c) {
                $val = (int)$c;
                if ($val > $max) $max = $val;
            }
            if ($max > 1) return $max;
        }

        if (preg_match_all('/\/N\s+(\d+)/i', $content, $m)) {
            $max = 1;
            foreach ($m[1] as $c) {
                $val = (int)$c;
                if ($val > $max) $max = $val;
            }
            if ($max > 1) return $max;
        }

        if (preg_match_all('/\/Type\s*\/Page\b/i', $content, $m)) {
            $c = count($m[0]);
            if ($c > 0) return $c;
        }

        if (preg_match_all('/stream[\r\n]+([\s\S]*?)endstream/i', $content, $streams)) {
            foreach ($streams[1] as $strData) {
                $uncompressed = @gzuncompress($strData);
                if (!$uncompressed) {
                    $uncompressed = @gzinflate($strData);
                }
                if (!$uncompressed && strlen($strData) > 2) {
                    $uncompressed = @gzuncompress(substr($strData, 2));
                }
                if ($uncompressed) {
                    if (preg_match_all('/\/Count\s+(\d+)/i', $uncompressed, $m)) {
                        $max = 1;
                        foreach ($m[1] as $c) {
                            $val = (int)$c;
                            if ($val > $max) $max = $val;
                        }
                        if ($max > 1) return $max;
                    }
                    if (preg_match_all('/\/Type\s*\/Page\b/i', $uncompressed, $m)) {
                        $c = count($m[0]);
                        if ($c > 0) return $c;
                    }
                }
            }
        }

        if (preg_match_all('/\/Page\b/i', $content, $m)) {
            $c = count($m[0]);
            if ($c > 0) return $c;
        }

        return 1;
    }
}
