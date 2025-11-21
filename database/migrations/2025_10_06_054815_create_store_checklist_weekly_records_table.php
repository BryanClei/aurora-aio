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
        Schema::create("store_checklist_weekly_records", function (
            Blueprint $table
        ) {
            $table->id();
            $table
                ->foreignId("store_checklist_id")
                ->constrained("store_checklists")
                ->onDelete("cascade");
            $table->integer("week")->index();
            $table->integer("month")->index();
            $table->integer("year")->index();

            $table->time("start_time")->nullable();
            $table->time("end_time")->nullable();

            $table->decimal("weekly_grade", 5, 2)->nullable();
            $table->boolean("is_auto_grade")->default(true);
            $table->enum("grade_source", ["auto", "manual"])->default("auto");

            $table->unsignedBigInteger("graded_by")->nullable();
            $table->string("status");
            $table->text("grade_notes")->nullable();

            $table->integer("store_visit")->nullable();
            $table->integer("expired")->nullable();
            $table->integer("condemned")->nullable();
            $table->longText("for_approval_reason")->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ["store_checklist_id", "week", "month", "year"],
                "unique_weekly_record"
            );

            $table->index(["week", "month", "year"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("store_checklist_weekly_records");
    }
};
