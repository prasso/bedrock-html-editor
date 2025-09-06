<?php

namespace Prasso\BedrockHtmlEditor\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Prasso\BedrockHtmlEditor\Models\HtmlComponent;
use App\Models\Site;

class HtmlComponentController extends Controller
{
    /**
     * Get components for a site
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getComponents(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'site_id' => 'required|exists:sites,id',
            'type' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $query = HtmlComponent::forSite($request->input('site_id'));

            if ($request->has('type')) {
                $query->ofType($request->input('type'));
            }

            $components = $query->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'components' => $components,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching components', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while fetching components.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific component
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getComponent(int $id): JsonResponse
    {
        try {
            $component = HtmlComponent::findOrFail($id);

            return response()->json([
                'success' => true,
                'component' => $component,
                'full_html' => $component->full_html,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching component', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while fetching the component.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new component
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createComponent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'html_content' => 'required|string',
            'css_content' => 'nullable|string',
            'js_content' => 'nullable|string',
            'thumbnail_url' => 'nullable|url',
            'is_global' => 'nullable|boolean',
            'site_id' => 'required_if:is_global,false|exists:sites,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Check if the user has permission to create a component for this site
            if (!$request->input('is_global', false) && !$this->userCanModifySite($request->input('site_id'))) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to create components for this site.',
                ], 403);
            }

            // Check if the user has permission to create global components
            if ($request->input('is_global', false) && !Auth::user()->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to create global components.',
                ], 403);
            }

            $component = HtmlComponent::create([
                'name' => $request->input('name'),
                'type' => $request->input('type'),
                'description' => $request->input('description'),
                'html_content' => $request->input('html_content'),
                'css_content' => $request->input('css_content'),
                'js_content' => $request->input('js_content'),
                'thumbnail_url' => $request->input('thumbnail_url'),
                'is_global' => $request->input('is_global', false),
                'site_id' => $request->input('is_global', false) ? null : $request->input('site_id'),
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'component' => $component,
                'full_html' => $component->full_html,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating component', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while creating the component.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing component
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateComponent(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'html_content' => 'nullable|string',
            'css_content' => 'nullable|string',
            'js_content' => 'nullable|string',
            'thumbnail_url' => 'nullable|url',
            'is_global' => 'nullable|boolean',
            'site_id' => 'nullable|exists:sites,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $component = HtmlComponent::findOrFail($id);
            
            // Check if the user has permission to update this component
            if (!$this->userCanModifyComponent($component)) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to modify this component.',
                ], 403);
            }

            // Check if the user is trying to change a component to global
            if ($request->has('is_global') && $request->input('is_global') && !$component->is_global && !Auth::user()->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to make components global.',
                ], 403);
            }

            // Check if the user is trying to change the site_id
            if ($request->has('site_id') && $request->input('site_id') != $component->site_id) {
                if (!$this->userCanModifySite($request->input('site_id'))) {
                    return response()->json([
                        'success' => false,
                        'error' => 'You do not have permission to move this component to the specified site.',
                    ], 403);
                }
            }

            $component->fill($request->only([
                'name',
                'type',
                'description',
                'html_content',
                'css_content',
                'js_content',
                'thumbnail_url',
                'is_global',
                'site_id',
            ]));

            // If component is global, set site_id to null
            if ($component->is_global) {
                $component->site_id = null;
            }

            $component->save();

            return response()->json([
                'success' => true,
                'component' => $component,
                'full_html' => $component->full_html,
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating component', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while updating the component.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a component
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deleteComponent(int $id): JsonResponse
    {
        try {
            $component = HtmlComponent::findOrFail($id);
            
            // Check if the user has permission to delete this component
            if (!$this->userCanModifyComponent($component)) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to delete this component.',
                ], 403);
            }

            $component->delete();

            return response()->json([
                'success' => true,
                'message' => 'Component deleted successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting component', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while deleting the component.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if the current user can modify a component
     *
     * @param HtmlComponent $component
     * @return bool
     */
    protected function userCanModifyComponent(HtmlComponent $component): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Super admins can modify any component
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Users can modify components they created
        if ($component->created_by === $user->id) {
            return true;
        }

        // Users can modify components for sites they have access to
        if (!$component->is_global && $component->site_id) {
            return $this->userCanModifySite($component->site_id);
        }

        return false;
    }

    /**
     * Check if the current user can modify a site
     *
     * @param int $siteId
     * @return bool
     */
    protected function userCanModifySite(int $siteId): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Super admins can modify any site
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check if the user is on the site's team
        $site = Site::find($siteId);
        if (!$site) {
            return false;
        }

        $team = $site->teamFromSite();
        if (!$team) {
            return false;
        }

        return $user->belongsToTeam($team);
    }
}
