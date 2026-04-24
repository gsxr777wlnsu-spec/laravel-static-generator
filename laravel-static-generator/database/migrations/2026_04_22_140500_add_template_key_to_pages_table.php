<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('template_key', 100)->default('blank')->after('locale');
            $table->index('template_key');
        });

        DB::table('pages')
            ->whereNull('template_key')
            ->update(['template_key' => 'blank']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex(['template_key']);
            $table->dropColumn('template_key');
        });
    }
};

