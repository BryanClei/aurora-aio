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
        Schema::create("areas", function (Blueprint $table) {
            $table->increments("id");
            $table->string("name");
            $table->unsignedInteger("region_id");
            $table
                ->foreign("region_id")
                ->reference("id")
                ->on("regions");
            $table
                ->unsignedInteger("area_head_id")
                ->nullable()
                ->index();
            $table
                ->foreign("area_head_id")
                ->references("id")
                ->on("users");
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("areas");
    }
};
