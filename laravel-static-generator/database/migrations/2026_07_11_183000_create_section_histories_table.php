<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->onDelete('cascade');
            $table->foreignId('page_id')->constrained()->onDelete('cascade');
            $table->string('type', 50);
            $table->json('content');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['section_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_histories');
    }
};
