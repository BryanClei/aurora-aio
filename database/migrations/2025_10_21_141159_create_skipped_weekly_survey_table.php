<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("skipped_weekly_survey", function (Blueprint $table) {
            $table->id();
            $table->integer("weekly_id");
            $table->integer("week")->index();
            $table->integer("month")->index();
            $table->integer("year")->index();
            $table->integer("approver_id")->nullable();
            $table->string("approver_name")->nullable();
            $table->string("approved_at")->nullable();
            $table->string("rejected_at")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("skipped_weekly_survey");
    }
};
