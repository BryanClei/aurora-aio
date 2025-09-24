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
        Schema::create("checklist_questions_responses", function (
            Blueprint $table
        ) {
            $table->id();
            $table
                ->foreignId("checklist_id")
                ->constrained("checklists")
                ->onDelete("cascade");
            $table->string("respondent_id")->nullable();
            $table->string("respondent_name")->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index("checklist_id");
            $table->index("respondent_name");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("checklist_questions_answers");
    }
};
