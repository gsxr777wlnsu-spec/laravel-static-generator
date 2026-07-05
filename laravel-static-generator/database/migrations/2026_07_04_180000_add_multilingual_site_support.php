<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            if (!Schema::hasColumn('sites', 'alternate_locales')) {
                $table->json('alternate_locales')->nullable()->after('default_locale');
            }
        });

        Schema::create('site_shared_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->longText('menu_html')->nullable();
            $table->longText('mobile_menu_html')->nullable();
            $table->longText('footer_html')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'locale']);
            $table->index('locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_shared_blocks');

        Schema::table('sites', function (Blueprint $table) {
            if (Schema::hasColumn('sites', 'alternate_locales')) {
                $table->dropColumn('alternate_locales');
            }
        });
    }
};
