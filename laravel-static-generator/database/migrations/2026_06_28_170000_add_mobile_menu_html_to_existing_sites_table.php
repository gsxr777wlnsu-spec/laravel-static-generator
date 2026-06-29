<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sites', 'mobile_menu_html')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->longText('mobile_menu_html')->nullable()->after('menu_html');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sites', 'mobile_menu_html')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->dropColumn('mobile_menu_html');
            });
        }
    }
};
