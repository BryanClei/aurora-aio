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
        Schema::create("stores", function (Blueprint $table) {
            $table->increments("id");
            $table->string("code");
            $table->string("name");
            $table->unsignedInteger("area_id");
            $table
                ->foreign("area_id")
                ->reference("id")
                ->on("areas");
            $table
                ->string("store_head_id")
                ->nullable()
                ->index();
            $table
                ->foreign("store_head_id")
                ->references("id")
                ->on("users");
            $table->unsignedBigInteger("checklist_id");
            $table
                ->foreign("checklist_id")
                ->reference("id")
                ->on("checklists");
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("stores");
    }
};
