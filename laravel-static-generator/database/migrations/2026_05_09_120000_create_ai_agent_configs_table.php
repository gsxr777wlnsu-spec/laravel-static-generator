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
        Schema::create('ai_agent_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 100)->default('openai');
            $table->text('api_key')->nullable();
            $table->string('api_base_url', 500)->nullable();
            $table->string('model_name', 150)->nullable();
            $table->decimal('temperature', 3, 2)->default(0.70);
            $table->string('tone', 100)->nullable();
            $table->unsignedInteger('max_tokens')->nullable();
            $table->decimal('top_p', 3, 2)->nullable();
            $table->decimal('frequency_penalty', 3, 2)->nullable();
            $table->decimal('presence_penalty', 3, 2)->nullable();
            $table->json('allowed_paths')->nullable();
            $table->json('allowed_sites')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('user_id');
            $table->index('provider');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_agent_configs');
    }
};
