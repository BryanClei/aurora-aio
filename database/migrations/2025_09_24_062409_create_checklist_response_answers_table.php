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
        Schema::create("checklist_response_answers", function (
            Blueprint $table
        ) {
            $table->id();
            $table
                ->foreignId("question_id")
                ->constrained("checklist_questions")
                ->onDelete("cascade");
            $table
                ->foreignId("response_id")
                ->constrained("store_checklist_responses")
                ->onDelete("cascade");
            $table->text("answer_text")->nullable();
            $table->json("selected_options")->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(["question_id", "response_id"]);
            $table->index("question_id");
            $table->index("response_id");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("checklist_response_answers");
    }
};
