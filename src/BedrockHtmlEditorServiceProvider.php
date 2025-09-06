<?php

namespace Prasso\BedrockHtmlEditor;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Prasso\BedrockHtmlEditor\Models\Extensions\UserExtension;
use Prasso\BedrockHtmlEditor\Middleware\BedrockHtmlEditorAuthorization;

class BedrockHtmlEditorServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the configuration file
        $this->mergeConfigFrom(
            __DIR__.'/../config/bedrock-html-editor.php', 'bedrock-html-editor'
        );

        // Register the services
        $this->app->singleton('bedrock-html-editor.agent-service', function ($app) {
            return new Services\BedrockAgentService();
        });

        $this->app->singleton('bedrock-html-editor.html-processing-service', function ($app) {
            return new Services\HtmlProcessingService(
                $app->make('bedrock-html-editor.agent-service')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the middleware
        $this->app['router']->aliasMiddleware(
            'bedrock-html-editor.auth', BedrockHtmlEditorAuthorization::class
        );

        // Register the routes
        $this->registerRoutes();

        // Register the migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register the User model extensions
        UserExtension::registerExtensions();

        // Publish the configuration file
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/bedrock-html-editor.php' => config_path('bedrock-html-editor.php'),
            ], 'bedrock-html-editor-config');

            // Publish the migrations
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'bedrock-html-editor-migrations');
        }
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }

    /**
     * Get the route group configuration array.
     */
    protected function routeConfiguration(): array
    {
        return [
            'middleware' => 'api',
            'prefix' => 'api',
            'namespace' => 'Prasso\\BedrockHtmlEditor\\Controllers',
        ];
    }
}
