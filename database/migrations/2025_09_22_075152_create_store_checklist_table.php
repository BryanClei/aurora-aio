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
        Schema::create("store_checklists", function (Blueprint $table) {
            $table->id();
            $table->string("code")->index();
            $table->string("store_id");
            $table->string("checklist_id");
            $table->decimal("grade", 5, 2)->nullable();
            $table->string("status")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("store_checklist");
    }
};
