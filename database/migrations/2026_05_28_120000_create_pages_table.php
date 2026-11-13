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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('title');
            $table->json('content')->nullable();
            $table->json('description')->nullable();
            $table->json('puck_data')->nullable();
            $table->string('status', 20)->default('active');
            $table->string('template')->default('puck');
            $table->string('type', 20)->default('page');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('post_id')->nullable()->constrained('posts')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            // SEO copy (meta, Open Graph, Twitter, robots, canonical) lives in the `seos` table.
            // Id of a Settings → Custom Code snippet this page loads (App\Support\CustomCode).
            $table->string('custom_code_id', 40)->nullable();
            $table->json('constant')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
