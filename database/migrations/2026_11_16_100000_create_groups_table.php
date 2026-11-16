<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();

            // Translatable, like every other name in the taxonomy.
            $table->json('name');

            // Same pool vocabulary categories, tags and brands pick from (see
            // App\Models\Type). Restrict, not cascade: a Type that still has
            // groups can only be deactivated.
            $table->foreignId('type_id')->constrained('types')->restrictOnDelete();

            $table->string('status', 20)->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
