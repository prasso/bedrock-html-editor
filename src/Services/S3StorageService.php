<?php

namespace Prasso\BedrockHtmlEditor\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Site;
use Prasso\BedrockHtmlEditor\Services\S3StorageHelper;

class S3StorageService
{
    /**
     * Store HTML content in S3
     *
     * @param string $html
     * @param string $filename
     * @param int $siteId
     * @param array $metadata
     * @return array
     */
    public function storeHtml(string $html, string $filename, int $siteId, array $metadata = []): array
    {
        try {
            $site = Site::find($siteId);
            if (!$site) {
                return [
                    'success' => false,
                    'error' => 'Site not found',
                ];
            }

            // Ensure the filename has .html extension
            if (!Str::endsWith($filename, '.html')) {
                $filename .= '.html';
            }

            // Create the path for the HTML file
            $path = $site->site_name . '/pages/' . $filename;
            
            // Store the HTML file in S3
            $stored = Storage::disk('s3')->put($path, $html);
            
            if (!$stored) {
                return [
                    'success' => false,
                    'error' => 'Failed to store HTML file in S3',
                ];
            }

            // Store metadata if provided
            if (!empty($metadata)) {
                $metadataPath = $site->site_name . '/pages/' . pathinfo($filename, PATHINFO_FILENAME) . '.meta.json';
                Storage::disk('s3')->put($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));
            }

            return [
                'success' => true,
                'path' => $path,
                'url' => S3StorageHelper::getPublicUrl($path),
                'size' => strlen($html),
            ];

        } catch (\Exception $e) {
            Log::error('Error storing HTML in S3', [
                'error' => $e->getMessage(),
                'filename' => $filename,
                'site_id' => $siteId,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to store HTML file: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Retrieve HTML content from S3
     *
     * @param string $path
     * @return array
     */
    public function retrieveHtml(string $path): array
    {
        try {
            if (!Storage::disk('s3')->exists($path)) {
                return [
                    'success' => false,
                    'error' => 'HTML file not found',
                ];
            }

            $html = Storage::disk('s3')->get($path);
            $metadata = [];

            // Check if metadata exists
            $metadataPath = pathinfo($path, PATHINFO_DIRNAME) . '/' . pathinfo($path, PATHINFO_FILENAME) . '.meta.json';
            if (Storage::disk('s3')->exists($metadataPath)) {
                $metadata = json_decode(Storage::disk('s3')->get($metadataPath), true) ?: [];
            }

            return [
                'success' => true,
                'html' => $html,
                'metadata' => $metadata,
                'size' => strlen($html),
                'last_modified' => Storage::disk('s3')->lastModified($path),
                'url' => S3StorageHelper::getPublicUrl($path),
            ];

        } catch (\Exception $e) {
            Log::error('Error retrieving HTML from S3', [
                'error' => $e->getMessage(),
                'path' => $path,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to retrieve HTML file: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Store HTML content for a specific site page
     *
     * @param string $html
     * @param int $siteId
     * @param int $pageId
     * @param array $metadata
     * @return array
     */
    public function storePageHtml(string $html, int $siteId, int $pageId, array $metadata = []): array
    {
        try {
            $site = Site::find($siteId);
            if (!$site) {
                return [
                    'success' => false,
                    'error' => 'Site not found',
                ];
            }

            // Create the filename based on the page ID
            $filename = 'page_' . $pageId . '.html';
            
            // Create the path for the HTML file
            $path = $site->site_name . '/pages/' . $filename;
            
            // Store the HTML file in S3
            $stored = Storage::disk('s3')->put($path, $html);
            
            if (!$stored) {
                return [
                    'success' => false,
                    'error' => 'Failed to store HTML file in S3',
                ];
            }

            // Store metadata if provided
            if (!empty($metadata)) {
                $metadataPath = $site->site_name . '/pages/' . pathinfo($filename, PATHINFO_FILENAME) . '.meta.json';
                Storage::disk('s3')->put($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));
            }

            return [
                'success' => true,
                'path' => $path,
                'url' => S3StorageHelper::getPublicUrl($path),
                'size' => strlen($html),
            ];

        } catch (\Exception $e) {
            Log::error('Error storing page HTML in S3', [
                'error' => $e->getMessage(),
                'site_id' => $siteId,
                'page_id' => $pageId,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to store page HTML file: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * List all HTML files for a site
     *
     * @param int $siteId
     * @return array
     */
    public function listSiteHtmlFiles(int $siteId): array
    {
        try {
            $site = Site::find($siteId);
            if (!$site) {
                return [
                    'success' => false,
                    'error' => 'Site not found',
                ];
            }

            // Get all files in the site's pages directory
            $path = $site->site_name . '/pages/';
            $files = Storage::disk('s3')->files($path);
            
            // Filter to only include HTML files
            $htmlFiles = array_filter($files, function($file) {
                return Str::endsWith($file, '.html');
            });

            // Format the results
            $results = [];
            foreach ($htmlFiles as $file) {
                $filename = basename($file);
                $metadataPath = pathinfo($file, PATHINFO_DIRNAME) . '/' . pathinfo($file, PATHINFO_FILENAME) . '.meta.json';
                $metadata = [];
                
                if (Storage::disk('s3')->exists($metadataPath)) {
                    $metadata = json_decode(Storage::disk('s3')->get($metadataPath), true) ?: [];
                }

                $results[] = [
                    'path' => $file,
                    'filename' => $filename,
                    'url' => S3StorageHelper::getPublicUrl($file),
                    'last_modified' => Storage::disk('s3')->lastModified($file),
                    'size' => Storage::disk('s3')->size($file),
                    'metadata' => $metadata,
                ];
            }

            return [
                'success' => true,
                'files' => $results,
                'count' => count($results),
            ];

        } catch (\Exception $e) {
            Log::error('Error listing site HTML files', [
                'error' => $e->getMessage(),
                'site_id' => $siteId,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to list site HTML files: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete an HTML file from S3
     *
     * @param string $path
     * @return array
     */
    public function deleteHtml(string $path): array
    {
        try {
            if (!Storage::disk('s3')->exists($path)) {
                return [
                    'success' => false,
                    'error' => 'HTML file not found',
                ];
            }

            // Delete the HTML file
            $deleted = Storage::disk('s3')->delete($path);
            
            if (!$deleted) {
                return [
                    'success' => false,
                    'error' => 'Failed to delete HTML file from S3',
                ];
            }

            // Delete metadata if it exists
            $metadataPath = pathinfo($path, PATHINFO_DIRNAME) . '/' . pathinfo($path, PATHINFO_FILENAME) . '.meta.json';
            if (Storage::disk('s3')->exists($metadataPath)) {
                Storage::disk('s3')->delete($metadataPath);
            }

            return [
                'success' => true,
                'message' => 'HTML file deleted successfully',
            ];

        } catch (\Exception $e) {
            Log::error('Error deleting HTML from S3', [
                'error' => $e->getMessage(),
                'path' => $path,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to delete HTML file: ' . $e->getMessage(),
            ];
        }
    }
}
