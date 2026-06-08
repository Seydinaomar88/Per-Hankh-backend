<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;

class CleanJsonResponse
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        $content = $response->getContent();
        
        if (is_string($content)) {
            $cleaned = preg_replace('/<!--(.*?)-->/s', '', $content);
            $cleaned = ltrim($cleaned);
            $response->setContent($cleaned);
            
            if (str_starts_with($cleaned, '{') || str_starts_with($cleaned, '[')) {
                $response->headers->set('Content-Type', 'application/json');
            }
        }
        
        return $response;
    }
}
