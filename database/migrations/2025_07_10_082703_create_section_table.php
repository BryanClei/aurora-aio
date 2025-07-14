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
        Schema::create("sections", function (Blueprint $table) {
            $table->increments("id");
            $table
                ->foreignId("checklist_id")
                ->constrained()
                ->onDelete("cascade");
            $table->string("title")->nullable();
            $table->text("description")->nullable();
            $table->double("total_percentage")->nullable();
            $table->integer("order")->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("sections");
    }
};
