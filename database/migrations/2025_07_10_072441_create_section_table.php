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
            $table
                ->string("title")
                ->nullable()
                ->index();
            $table->longText("description")->nullable();
            $table->decimal("percentage", 5, 2);
            $table->integer("order")->default(1);
            $table->timestamps();
            $table->softDeletes();
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
