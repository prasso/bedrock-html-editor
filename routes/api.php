<?php

use Illuminate\Support\Facades\Route;
use Prasso\BedrockHtmlEditor\Controllers\HtmlEditorController;
use Prasso\BedrockHtmlEditor\Controllers\HtmlTemplateController;
use Prasso\BedrockHtmlEditor\Controllers\HtmlComponentController;
use Prasso\BedrockHtmlEditor\Middleware\BedrockHtmlEditorAuthorization;

/*
|--------------------------------------------------------------------------
| Bedrock HTML Editor API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Bedrock HTML Editor package.
|
*/

Route::middleware(['auth:sanctum'])->prefix('api/bedrock-html-editor')->group(function () {
    // HTML Editor routes - require site owner permissions
    Route::middleware([BedrockHtmlEditorAuthorization::class.':site-owner'])->group(function () {
        Route::post('/modify', [HtmlEditorController::class, 'modifyHtml']);
        Route::post('/create', [HtmlEditorController::class, 'createHtml']);
        Route::get('/modifications', [HtmlEditorController::class, 'getModificationHistory']);
        Route::get('/modifications/{id}', [HtmlEditorController::class, 'getModification']);
        Route::post('/modifications/{id}/apply', [HtmlEditorController::class, 'applyModification']);
    });
    
    // HTML Template routes - read available to all users, write requires admin
    Route::get('/templates', [HtmlTemplateController::class, 'getTemplates']);
    Route::get('/templates/{id}', [HtmlTemplateController::class, 'getTemplate']);
    
    Route::middleware([BedrockHtmlEditorAuthorization::class.':admin'])->group(function () {
        Route::post('/templates', [HtmlTemplateController::class, 'createTemplate']);
        Route::put('/templates/{id}', [HtmlTemplateController::class, 'updateTemplate']);
        Route::delete('/templates/{id}', [HtmlTemplateController::class, 'deleteTemplate']);
    });
    
    // HTML Component routes - read available to all users, write requires site owner
    Route::get('/components', [HtmlComponentController::class, 'getComponents']);
    Route::get('/components/{id}', [HtmlComponentController::class, 'getComponent']);
    
    Route::middleware([BedrockHtmlEditorAuthorization::class.':site-owner'])->group(function () {
        Route::post('/components', [HtmlComponentController::class, 'createComponent']);
        Route::put('/components/{id}', [HtmlComponentController::class, 'updateComponent']);
        Route::delete('/components/{id}', [HtmlComponentController::class, 'deleteComponent']);
    });
});
