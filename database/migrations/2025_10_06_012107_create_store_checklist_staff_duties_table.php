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
        Schema::create("store_checklist_staff_duties", function (
            Blueprint $table
        ) {
            $table->id();
            $table->unsignedBigInteger("store_checklist_weekly_records_id");
            $table
                ->foreignId("store_checklist_id")
                ->constrained("store_checklists")
                ->onDelete("cascade");

            $table->unsignedBigInteger("staff_id")->nullable();
            $table->string("staff_name");

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("store_checklist_staff_duties");
    }
};
