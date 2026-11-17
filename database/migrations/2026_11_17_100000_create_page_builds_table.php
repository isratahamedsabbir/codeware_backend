<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * What the GrapesJS page builder saves: one row per page and locale. Kept apart
 * from pages.puck_data / pages.content, which belong to the Puck editor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_builds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->longText('project')->nullable(); // GrapesJS project JSON, reloaded by the editor
            $table->longText('html')->nullable();    // what the theme prints
            $table->longText('css')->nullable();
            $table->timestamps();

            $table->unique(['page_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_builds');
    }
};
