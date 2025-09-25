<?php

namespace Prasso\BedrockHtmlEditor\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BedrockHtmlEditorAuthorization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $role = 'user'): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Authentication required.',
            ], 401);
        }

        // Super admins have access to everything
        if ($user->isSuperAdmin()) {
            return $next($request);
        }
        $site = null;
        // If site_id is provided in the request, check if user is on the site's team
        if ($request->has('site_id')) {
            $siteId = $request->input('site_id');
            $site = \App\Models\Site::find($siteId);
        }
        // Check role-based permissions
        switch ($role) {
            case 'admin':
                if (!$user->isInstructor($site)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized. Admin privileges required.',
                    ], 403);
                }
                break;
                
            case 'site-owner':
                
                if (!$site) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Site not found.',
                    ], 404);
                }
                
                $team = $site->teamFromSite();
                if (!$team || !$user->belongsToTeam($team)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized. You do not have access to this site.',
                    ], 403);
                }
                
                break;
                
            case 'user':
                // Basic authenticated user, no additional checks needed
                break;
                
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid role specified.',
                ], 500);
        }

        return $next($request);
    }
}
