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
        Schema::create('bhe_html_modifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('site_id');
            $table->unsignedBigInteger('page_id')->nullable();
            $table->string('title');
            $table->text('prompt');
            $table->text('original_html')->nullable();
            $table->text('modified_html');
            $table->string('storage_path')->nullable();
            $table->string('session_id')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('site_id')->references('id')->on('sites')->onDelete('cascade');
            $table->foreign('page_id')->references('id')->on('site_pages')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bhe_html_modifications');
    }
};
