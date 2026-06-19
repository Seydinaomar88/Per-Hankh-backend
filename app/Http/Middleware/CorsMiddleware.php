<?php
// app/Http/Middleware/CorsMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response; // 🔥 AJOUTER CET IMPORT

class CorsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Gérer les requêtes OPTIONS (preflight)
        if ($request->getMethod() === 'OPTIONS') {
            $response = response('', 200);
            $this->setCorsHeaders($response);
            return $response;
        }

        $response = $next($request);
        $this->setCorsHeaders($response);
        return $response;
    }

    /**
     * @param \Illuminate\Http\Response|\Illuminate\Http\JsonResponse $response
     */
    private function setCorsHeaders($response): void // 🔥 AJOUTER LE TYPE DE RETOUR
    {
        $response->headers->set('Access-Control-Allow-Origin', 'http://localhost:3000');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, Accept');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '86400');
    }
}