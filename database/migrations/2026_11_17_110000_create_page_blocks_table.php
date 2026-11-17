<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Reusable blocks saved from the GrapesJS page builder ("Save as block") —
 * one shared library, offered in the builder's Blocks panel on every page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('content'); // HTML with its <style>, as GrapesJS takes it
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_blocks');
    }
};
