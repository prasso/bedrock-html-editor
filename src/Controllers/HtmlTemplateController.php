<?php

namespace Prasso\BedrockHtmlEditor\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Prasso\BedrockHtmlEditor\Models\HtmlTemplate;

class HtmlTemplateController extends Controller
{
    /**
     * Get all templates or templates by category
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTemplates(Request $request): JsonResponse
    {
        try {
            $query = HtmlTemplate::active();

            if ($request->has('category')) {
                $query->inCategory($request->input('category'));
            }

            $templates = $query->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'templates' => $templates,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching templates', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while fetching templates.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific template
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getTemplate(int $id): JsonResponse
    {
        try {
            $template = HtmlTemplate::findOrFail($id);

            return response()->json([
                'success' => true,
                'template' => $template,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching template', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while fetching the template.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new template
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createTemplate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'html_content' => 'required|string',
            'thumbnail_url' => 'nullable|url',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $template = HtmlTemplate::create([
                'name' => $request->input('name'),
                'category' => $request->input('category'),
                'description' => $request->input('description'),
                'html_content' => $request->input('html_content'),
                'thumbnail_url' => $request->input('thumbnail_url'),
                'is_active' => $request->input('is_active', true),
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'template' => $template,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating template', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while creating the template.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing template
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateTemplate(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'html_content' => 'nullable|string',
            'thumbnail_url' => 'nullable|url',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $template = HtmlTemplate::findOrFail($id);
            
            // Check if the user has permission to update this template
            if (!$this->userCanModifyTemplate($template)) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to modify this template.',
                ], 403);
            }

            $template->fill($request->only([
                'name',
                'category',
                'description',
                'html_content',
                'thumbnail_url',
                'is_active',
            ]));

            $template->save();

            return response()->json([
                'success' => true,
                'template' => $template,
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating template', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while updating the template.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a template
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deleteTemplate(int $id): JsonResponse
    {
        try {
            $template = HtmlTemplate::findOrFail($id);
            
            // Check if the user has permission to delete this template
            if (!$this->userCanModifyTemplate($template)) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to delete this template.',
                ], 403);
            }

            $template->delete();

            return response()->json([
                'success' => true,
                'message' => 'Template deleted successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting template', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while deleting the template.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if the current user can modify a template
     *
     * @param HtmlTemplate $template
     * @return bool
     */
    protected function userCanModifyTemplate(HtmlTemplate $template): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Super admins can modify any template
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Users can modify templates they created
        return $template->created_by === $user->id;
    }
}
