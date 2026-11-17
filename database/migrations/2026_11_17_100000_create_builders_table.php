<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Everything the GrapesJS builder saves — a table of its own, tied to nothing
 * else: no page, no SEO. A design is reached by its own slug (the shared `slugs`
 * table, see HasSlug), like any other public record.
 *
 *   type = 'design' a page design: `name`, and per locale (JSON keyed
 *                   by locale code) `project` (what the editor reloads) and
 *                   `html` + `css` (what the public address prints).
 *   type = 'block'  a reusable block ("Save as block"): `name` + `content`
 *                   (HTML with its <style>), offered in every design's Blocks panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('builders', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index(); // design | block
            $table->string('name');
            $table->json('project')->nullable();
            $table->json('html')->nullable();
            $table->json('css')->nullable();
            $table->longText('content')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builders');
    }
};
