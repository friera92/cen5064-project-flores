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
        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->decimal('daily_rate', 8, 2);
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->string('availability_status')->default('AVAILABLE'); // Enum: AVAILABLE, IN_USE, MAINTENANCE, RETIRED
            $table->string('condition')->default('CREATED'); // Enum: CREATED, NEW, GOOD, FAIR, POOR
            $table->string('picture')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tools');
    }
};
