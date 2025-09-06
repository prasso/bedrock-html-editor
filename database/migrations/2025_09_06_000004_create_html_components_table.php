<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bhe_html_components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->text('description')->nullable();
            $table->text('html_content');
            $table->text('css_content')->nullable();
            $table->text('js_content')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->boolean('is_global')->default(false);
            $table->unsignedBigInteger('site_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            $table->foreign('site_id')->references('id')->on('sites')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bhe_html_components');
    }
};
