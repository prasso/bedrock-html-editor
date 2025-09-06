<?php

namespace Prasso\BedrockHtmlEditor\Services;

use Illuminate\Support\Facades\Config;

class S3StorageHelper
{
    /**
     * Generate a public URL for an S3 object
     *
     * @param string $path
     * @return string
     */
    public static function getPublicUrl(string $path): string
    {
        // Use the app URL and append the storage path
        return Config::get('app.url') . '/storage/' . $path;
    }
}
