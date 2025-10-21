<?php

namespace App\Services;

use Illuminate\Support\Str;

class InputValidationService
{
    /**
     * Sanitize HTML content while preserving safe tags
     */
    public function sanitizeHtml(string $html): string
    {
        $allowedTags = '<p><br><strong><em><u><ul><ol><li><a><h1><h2><h3><h4><h5><h6><blockquote><code><pre>';
        
        // Strip tags except allowed ones
        $cleaned = strip_tags($html, $allowedTags);
        
        // Remove javascript: and data: protocols from links
        $cleaned = preg_replace('/(<a[^>]+href=[\"\'])(javascript:|data:)/i', '$1#', $cleaned);
        
        // Remove on* event handlers
        $cleaned = preg_replace('/<([^>]+)\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '<$1', $cleaned);
        
        return $cleaned;
    }

    /**
     * Validate and sanitize file upload
     */
    public function validateFileUpload(string $filename, array $allowedExtensions): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Check if extension is allowed
        if (!in_array($extension, $allowedExtensions)) {
            return false;
        }
        
        // Check for double extensions (e.g., file.php.jpg)
        $parts = explode('.', $filename);
        if (count($parts) > 2) {
            foreach (array_slice($parts, 0, -1) as $part) {
                if (in_array(strtolower($part), ['php', 'phtml', 'php3', 'php4', 'php5', 'phar'])) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Sanitize filename for safe storage
     */
    public function sanitizeFilename(string $filename): string
    {
        // Get extension
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        
        // Remove special characters
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
        
        // Limit length
        $basename = Str::limit($basename, 100, '');
        
        return $basename . '.' . $extension;
    }

    /**
     * Validate SQL input to prevent injection
     */
    public function validateSqlInput(string $input): bool
    {
        // Check for common SQL injection patterns
        $patterns = [
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(\bUPDATE\b.*\bSET\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(\bEXEC\b|\bEXECUTE\b)/i',
            '/(--|#|\/\*|\*\/)/i',
            '/(\bOR\b.*=.*)/i',
            '/(\bAND\b.*=.*)/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Sanitize URL
     */
    public function sanitizeUrl(string $url): ?string
    {
        // Remove whitespace
        $url = trim($url);
        
        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        
        // Check protocol
        $parsed = parse_url($url);
        if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'])) {
            return null;
        }
        
        return $url;
    }

    /**
     * Validate email address
     */
    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Sanitize user input for display
     */
    public function sanitizeForDisplay(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
