<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;

class LogUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log for authenticated users
        if (!Auth::check()) {
            return $response;
        }

        // Define which routes to log
        $loggableRoutes = [
            'GET' => [
                '/api/projects/*' => 'viewed project',
                '/api/activities/*' => 'viewed activity',
                '/api/mitras/*' => 'viewed mitra',
                '/api/barang-certificates/*' => 'viewed barang certificate',
                '/api/certificates/*' => 'viewed certificate',
            ],
            'POST' => [
                '/api/projects' => 'created project',
                '/api/activities' => 'created activity',
                '/api/mitras' => 'created mitra',
                '/api/barang-certificates' => 'created barang certificate',
                '/api/certificates' => 'created certificate',
            ],
            'PUT' => [
                '/api/projects/*' => 'updated project',
                '/api/activities/*' => 'updated activity',
                '/api/mitras/*' => 'updated mitra',
                '/api/barang-certificates/*' => 'updated barang certificate',
                '/api/certificates/*' => 'updated certificate',
            ],
            'DELETE' => [
                '/api/projects/*' => 'deleted project',
                '/api/activities/*' => 'deleted activity',
                '/api/mitras/*' => 'deleted mitra',
                '/api/barang-certificates/*' => 'deleted barang certificate',
                '/api/certificates/*' => 'deleted certificate',
            ]
        ];

        $method = $request->method();
        $path = $request->path();
        
        // Check if current route should be logged
        $action = null;
        if (isset($loggableRoutes[$method])) {
            foreach ($loggableRoutes[$method] as $pattern => $description) {
                if ($this->matchPattern($pattern, $path)) {
                    $action = $description;
                    break;
                }
            }
        }

        // Log the activity if it matches our criteria
        if ($action) {
            $service = new ActivityLogService();
            $service->log(
                $this->getActionFromDescription($action),
                null,
                null,
                null,
                $action
            );
        }

        return $response;
    }

    /**
     * Check if path matches pattern
     */
    protected function matchPattern($pattern, $path)
    {
        $pattern = str_replace('*', '.*', $pattern);
        $pattern = '#^' . $pattern . '$#';
        return preg_match($pattern, $path);
    }

    /**
     * Extract action from description
     */
    protected function getActionFromDescription($description)
    {
        $parts = explode(' ', $description);
        return $parts[0]; // created, updated, deleted, viewed
    }
}
