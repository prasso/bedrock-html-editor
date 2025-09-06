<?php

namespace Prasso\BedrockHtmlEditor\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Prasso\BedrockHtmlEditor\Services\HtmlProcessingService;
use Prasso\BedrockHtmlEditor\Models\HtmlModification;
use Prasso\BedrockHtmlEditor\Models\AiPromptHistory;
use App\Models\SitePages;
use App\Models\Site;

class HtmlEditorController extends Controller
{
    protected HtmlProcessingService $htmlProcessingService;

    public function __construct(HtmlProcessingService $htmlProcessingService)
    {
        $this->htmlProcessingService = $htmlProcessingService;
    }

    /**
     * Modify existing HTML content based on user prompt
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function modifyHtml(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'html' => 'required|string',
            'prompt' => 'required|string|max:1000',
            'site_id' => 'required|exists:sites,id',
            'page_id' => 'nullable|exists:site_pages,id',
            'title' => 'required|string|max:255',
            'session_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Process the HTML modification
            $result = $this->htmlProcessingService->modifyHtml(
                $request->input('html'),
                $request->input('prompt'),
                $request->input('session_id')
            );

            if (!$result['success']) {
                return response()->json($result, 500);
            }

            // Save the modification to the database
            $modification = HtmlModification::create([
                'user_id' => Auth::id(),
                'site_id' => $request->input('site_id'),
                'page_id' => $request->input('page_id'),
                'title' => $request->input('title'),
                'prompt' => $request->input('prompt'),
                'original_html' => $request->input('html'),
                'modified_html' => $result['modified_html'],
                'session_id' => $result['session_id'],
                'metadata' => [
                    'validation' => $result['validation'],
                    'size_before' => $result['size_before'],
                    'size_after' => $result['size_after'],
                ],
            ]);

            // Save the prompt history
            AiPromptHistory::create([
                'user_id' => Auth::id(),
                'modification_id' => $modification->id,
                'prompt' => $request->input('prompt'),
                'response' => $result['modified_html'],
                'session_id' => $result['session_id'],
                'success' => true,
            ]);

            // Save the HTML to storage if requested
            if ($request->input('save_to_storage', false)) {
                $filename = Str::slug($request->input('title')) . '-' . time() . '.html';
                $storageResult = $this->htmlProcessingService->saveHtml(
                    $result['modified_html'],
                    $filename,
                    [
                        'modification_id' => $modification->id,
                        'title' => $request->input('title'),
                        'prompt' => $request->input('prompt'),
                    ]
                );

                if ($storageResult['success']) {
                    $modification->storage_path = $storageResult['path'];
                    $modification->save();
                }
            }

            return response()->json([
                'success' => true,
                'modification' => $modification,
                'html' => $result['modified_html'],
            ]);

        } catch (\Exception $e) {
            Log::error('Error in HTML modification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while processing your request.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new HTML content based on user prompt
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createHtml(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'prompt' => 'required|string|max:1000',
            'site_id' => 'required|exists:sites,id',
            'title' => 'required|string|max:255',
            'session_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Process the HTML creation
            $result = $this->htmlProcessingService->createHtml(
                $request->input('prompt'),
                $request->input('session_id')
            );

            if (!$result['success']) {
                return response()->json($result, 500);
            }

            // Save the modification to the database
            $modification = HtmlModification::create([
                'user_id' => Auth::id(),
                'site_id' => $request->input('site_id'),
                'title' => $request->input('title'),
                'prompt' => $request->input('prompt'),
                'modified_html' => $result['html'],
                'session_id' => $result['session_id'],
                'metadata' => [
                    'validation' => $result['validation'],
                    'size' => $result['size'],
                ],
            ]);

            // Save the prompt history
            AiPromptHistory::create([
                'user_id' => Auth::id(),
                'modification_id' => $modification->id,
                'prompt' => $request->input('prompt'),
                'response' => $result['html'],
                'session_id' => $result['session_id'],
                'success' => true,
            ]);

            // Save the HTML to storage if requested
            if ($request->input('save_to_storage', false)) {
                $filename = Str::slug($request->input('title')) . '-' . time() . '.html';
                $storageResult = $this->htmlProcessingService->saveHtml(
                    $result['html'],
                    $filename,
                    [
                        'modification_id' => $modification->id,
                        'title' => $request->input('title'),
                        'prompt' => $request->input('prompt'),
                    ]
                );

                if ($storageResult['success']) {
                    $modification->storage_path = $storageResult['path'];
                    $modification->save();
                }
            }

            return response()->json([
                'success' => true,
                'modification' => $modification,
                'html' => $result['html'],
            ]);

        } catch (\Exception $e) {
            Log::error('Error in HTML creation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while processing your request.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get modification history for a site or page
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getModificationHistory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'site_id' => 'required|exists:sites,id',
            'page_id' => 'nullable|exists:site_pages,id',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $query = HtmlModification::where('site_id', $request->input('site_id'));

            if ($request->has('page_id')) {
                $query->where('page_id', $request->input('page_id'));
            }

            $limit = $request->input('limit', 20);
            $modifications = $query->orderBy('created_at', 'desc')
                                  ->limit($limit)
                                  ->get();

            return response()->json([
                'success' => true,
                'modifications' => $modifications,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching modification history', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while fetching modification history.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific HTML modification
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getModification(int $id): JsonResponse
    {
        try {
            $modification = HtmlModification::findOrFail($id);

            return response()->json([
                'success' => true,
                'modification' => $modification,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching modification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while fetching the modification.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Apply a modification to a site page
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function applyModification(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page_id' => 'required|exists:site_pages,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $modification = HtmlModification::findOrFail($id);
            $page = SitePages::findOrFail($request->input('page_id'));

            // Check if the user has permission to modify this page
            if (!$this->userCanModifyPage($page)) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to modify this page.',
                ], 403);
            }

            // Update the page content
            $page->description = $modification->modified_html;
            $page->save();

            // Update the modification to link it to the page
            $modification->page_id = $page->id;
            $modification->is_published = true;
            $modification->save();

            return response()->json([
                'success' => true,
                'message' => 'Modification applied successfully.',
                'page' => $page,
                'modification' => $modification,
            ]);

        } catch (\Exception $e) {
            Log::error('Error applying modification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while applying the modification.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if the current user can modify a page
     *
     * @param SitePages $page
     * @return bool
     */
    protected function userCanModifyPage(SitePages $page): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Super admins can modify any page
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check if the user is on the site's team
        $site = Site::find($page->fk_site_id);
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
