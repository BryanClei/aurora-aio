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
        Schema::create("questions", function (Blueprint $table) {
            $table->increments("id");
            $table->unsignedInteger("section_id");
            $table
                ->foreign("section_id")
                ->references("id")
                ->on("sections")
                ->onDelete("cascade");
            $table->string("title")->index();
            $table->longText("question_text");
            $table->unsignedInteger("order_number")->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("questions");
    }
};
