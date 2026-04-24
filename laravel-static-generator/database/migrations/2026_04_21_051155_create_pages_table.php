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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->string('slug');
            $table->string('title');
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('canonical', 500)->nullable();
            $table->json('og_data')->nullable();
            $table->json('json_ld')->nullable();
            $table->enum('status', ['published', 'draft', 'archived'])->default('draft');
            $table->string('locale', 10)->default('en');
            $table->foreignId('parent_page_id')->nullable()->constrained('pages')->onDelete('set null');
            $table->timestamps();
            
            $table->unique(['site_id', 'slug', 'locale']);
            $table->index(['site_id', 'status']);
            $table->index('locale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
