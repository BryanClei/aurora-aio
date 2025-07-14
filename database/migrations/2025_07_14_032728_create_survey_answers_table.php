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
        Schema::create("survey_answers", function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId("survey_session_id")
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger("question_id");
            $table
                ->foreign("question_id")
                ->references("id")
                ->on("questions")
                ->onDelete("cascade");

            $table->boolean("is_compliant");
            $table->text("remarks")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("survey_answers");
    }
};
