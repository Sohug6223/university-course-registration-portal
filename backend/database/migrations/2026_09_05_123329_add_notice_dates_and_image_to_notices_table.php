<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->string('title', 255)->after('id');
            $table->text('description')->after('title');
            $table->date('published_date')->after('description');
            $table->date('expiry_date')->nullable()->after('published_date');
            $table->string('image_path')->nullable()->after('expiry_date');
            $table->enum('status', ['Active', 'Inactive'])
                ->default('Active')
                ->after('image_path');
            $table->foreignId('created_by')
                ->nullable()
                ->after('status')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropForeign(['created_by']);

            $table->dropColumn([
                'title',
                'description',
                'published_date',
                'expiry_date',
                'image_path',
                'status',
                'created_by',
            ]);
        });
    }
};