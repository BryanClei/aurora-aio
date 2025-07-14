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
        Schema::create("audit_trails", function (Blueprint $table) {
            $table->increments("id");
            $table->string("module_type");
            $table->string("module_name");
            $table->unsignedBigInteger("module_id")->index();
            $table->string("action");
            $table->unsignedBigInteger("action_by");
            $table->string("action_by_name");
            $table->text("log_info")->nullable();
            $table->json("previous_data")->nullable();
            $table->json("new_data")->nullable();
            $table->string("remarks")->nullable();
            $table->string("ip_address")->nullable();
            $table->string("user_agent")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("audit_trails");
    }
};
