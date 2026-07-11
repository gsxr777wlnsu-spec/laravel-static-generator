<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_prompt_rules', function (Blueprint $table) {
            $table->id();
            $table->string('template_set', 100);
            $table->string('page_key', 100);
            $table->string('field_key', 255);
            $table->text('rule')->nullable();
            $table->timestamps();

            $table->unique(['template_set', 'page_key', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_prompt_rules');
    }
};
