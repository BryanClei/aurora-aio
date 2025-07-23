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
        Schema::create("survey_sessions", function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger("store_id");
            $table
                ->foreign("store_id")
                ->references("id")
                ->on("stores")
                ->onDelete("cascade");
            $table
                ->foreignId("checklist_id")
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedBigInteger("surveyor_id");
            $table->string("surveyor_name");
            $table->date("survey_date");
            $table->longText("remarks")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("survey_sessions");
    }
};
