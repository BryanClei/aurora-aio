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
        Schema::create("checklist_question_options", function (
            Blueprint $table
        ) {
            $table->id();
            $table
                ->foreignId("question_id")
                ->constrained("checklist_questions")
                ->onDelete("cascade");
            $table->string("option_text", 500);
            $table->unsignedBigInteger("score_rating_id")->nullable();
            $table->integer("order_index")->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(["question_id", "order_index"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("checklist_question_options");
    }
};
