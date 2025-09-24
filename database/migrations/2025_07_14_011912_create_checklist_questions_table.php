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
        Schema::create("checklist_questions", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("section_id");
            $table
                ->foreign("section_id")
                ->references("id")
                ->on("checklist_sections")
                ->onDelete("cascade");
            $table->text("question_text");
            $table->enum("question_type", [
                "paragraph",
                "multiple_choice",
                "checkboxes",
                "rating",
                "date",
                "number",
            ]);
            $table->integer("order_index")->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(["section_id", "order_index"]);
            $table->index("question_type");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("checklist_questions");
    }
};
