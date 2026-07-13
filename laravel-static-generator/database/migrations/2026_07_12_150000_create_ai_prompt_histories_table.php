<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_prompt_histories', function (Blueprint $table) {
            $table->id();
            $table->string('template_set', 100);
            $table->string('page_key', 255);
            $table->string('module_key', 255);
            $table->string('locale', 20);
            $table->string('field_key', 1000);
            $table->char('scope_hash', 64);
            $table->text('prompt');
            $table->char('prompt_hash', 64);
            $table->boolean('is_favorite')->default(false);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->unique(['scope_hash', 'prompt_hash', 'is_favorite'], 'ai_prompt_history_unique');
            $table->index('scope_hash', 'ai_prompt_history_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_prompt_histories');
    }
};
