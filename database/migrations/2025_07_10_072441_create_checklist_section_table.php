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
        Schema::create("checklist_sections", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("checklist_id")
                ->constrained()
                ->onDelete("cascade");
            $table
                ->string("title")
                ->nullable()
                ->index();
            $table->decimal("percentage", 5, 2)->nullable();
            $table->integer("order_index")->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(["checklist_id", "order_index"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("checklist_sections");
    }
};
