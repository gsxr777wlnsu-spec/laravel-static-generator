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
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->unique();
            $table->string('template_set', 100);
            $table->string('output_path', 500);
            $table->enum('status', ['active', 'inactive', 'draft'])->default('draft');
            $table->string('locale', 10)->default('en');
            $table->string('default_locale', 10)->default('en');
            $table->string('sftp_host')->nullable();
            $table->unsignedInteger('sftp_port')->default(22);
            $table->string('sftp_username')->nullable();
            $table->text('sftp_password')->nullable();
            $table->text('sftp_private_key')->nullable();
            $table->enum('sftp_auth_method', ['password', 'key'])->default('key');
            $table->string('sftp_remote_path', 500)->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
