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
            $table->string("code")->index();
            $table->string("name")->index();
            $table->string("region_id");
            $table->string("area_id");
            $table->timestamps();
            $table->softDeletes();
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
