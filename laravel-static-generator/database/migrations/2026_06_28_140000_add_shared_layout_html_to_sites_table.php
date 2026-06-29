<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->longText('menu_html')->nullable()->after('default_locale');
            $table->longText('mobile_menu_html')->nullable()->after('menu_html');
            $table->longText('footer_html')->nullable()->after('mobile_menu_html');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['menu_html', 'mobile_menu_html', 'footer_html']);
        });
    }
};
