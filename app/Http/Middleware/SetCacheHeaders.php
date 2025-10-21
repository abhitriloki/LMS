<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCacheHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $cacheControl = 'public'): Response
    {
        $response = $next($request);

        // Only cache GET requests
        if (!$request->isMethod('GET')) {
            return $response;
        }

        // Don't cache if user is authenticated (for personalized content)
        if ($request->user()) {
            return $response;
        }

        // Set cache headers based on content type
        $contentType = $response->headers->get('Content-Type', '');

        if (str_contains($contentType, 'text/html')) {
            // HTML pages: cache for 5 minutes
            $response->headers->set('Cache-Control', 'public, max-age=300, must-revalidate');
        } elseif (str_contains($contentType, 'image/')) {
            // Images: cache for 1 year
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        } elseif (str_contains($contentType, 'text/css') || str_contains($contentType, 'javascript')) {
            // CSS and JS: cache for 1 year (versioned by Vite)
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        } elseif (str_contains($contentType, 'font/')) {
            // Fonts: cache for 1 year
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        } else {
            // Default: cache for 1 hour
            $response->headers->set('Cache-Control', "{$cacheControl}, max-age=3600");
        }

        // Add ETag for validation
        if (!$response->headers->has('ETag')) {
            $etag = md5($response->getContent());
            $response->headers->set('ETag', $etag);

            // Check if client has cached version
            if ($request->header('If-None-Match') === $etag) {
                return response('', 304)->withHeaders($response->headers->all());
            }
        }

        return $response;
    }
}
