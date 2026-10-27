
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add logical deletion support.
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('tools', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Prevent user and category deletion from
        // physically removing equipment.
        Schema::table('tools', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropForeign(['category_id']);

            $table->foreign('owner_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->restrictOnDelete();
        });

        // Protect historical reservations.
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['tool_id']);
            $table->dropForeign(['borrower_id']);

            $table->foreign('tool_id')
                ->references('id')
                ->on('tools')
                ->restrictOnDelete();

            $table->foreign('borrower_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });

        // Protect historical reviews.
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['tool_id']);
            $table->dropForeign(['reviewer_id']);
            $table->dropForeign(['reviewee_id']);
            $table->dropForeign(['reservation_id']);

            $table->foreign('tool_id')
                ->references('id')
                ->on('tools')
                ->restrictOnDelete();

            $table->foreign('reviewer_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('reviewee_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('reservation_id')
                ->references('id')
                ->on('reservations')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['tool_id']);
            $table->dropForeign(['borrower_id']);

            $table->foreign('tool_id')
                ->references('id')
                ->on('tools')
                ->cascadeOnDelete();

            $table->foreign('borrower_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        Schema::table('tools', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropForeign(['category_id']);

            $table->foreign('owner_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->cascadeOnDelete();
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['tool_id']);
            $table->dropForeign(['reviewer_id']);
            $table->dropForeign(['reviewee_id']);
            $table->dropForeign(['reservation_id']);

            // Restore the original deletion behavior.
            $table->foreign('tool_id')
                ->references('id')
                ->on('tools')
                ->cascadeOnDelete();

            $table->foreign('reviewer_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('reviewee_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('reservation_id')
                ->references('id')
                ->on('reservations')
                ->cascadeOnDelete();
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('tools', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
