<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->string('module')->nullable()->after('type');
            $table->string('module_key')->nullable()->after('module');
            $table->string('heading')->nullable()->after('module_key');
            $table->string('subheading')->nullable()->after('heading');
            $table->text('description')->nullable()->after('subheading');
            $table->longText('raw_html')->nullable()->after('description');
            $table->string('class')->nullable()->after('raw_html');
            $table->string('identifier')->nullable()->after('class');
            $table->json('settings')->nullable()->after('identifier');
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn(['module', 'module_key', 'heading', 'subheading', 'description', 'raw_html', 'class', 'identifier', 'settings']);
        });
    }
};