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
        Schema::create('bhe_ai_prompt_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('modification_id')->nullable();
            $table->text('prompt');
            $table->text('response')->nullable();
            $table->string('session_id')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('success')->default(true);
            $table->string('error_message')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('modification_id')->references('id')->on('bhe_html_modifications')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bhe_ai_prompt_history');
    }
};
