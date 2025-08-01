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
        Schema::create("users", function (Blueprint $table) {
            $table->increments("id");
            $table->string("id_prefix");
            $table->string("id_no");
            $table->index(["id_prefix", "id_no"]);
            $table->string("first_name");
            $table->string("middle_name")->nullable();
            $table->string("last_name");
            $table->string("suffix")->nullable();
            $table->string("position_name")->nullable();
            $table->string("mobile_number")->nullable();
            $table->enum("gender", ["male", "female"]);
            $table
                ->unsignedInteger("one_charging_id")
                ->nullable()
                ->index();

            $table
                ->foreign("one_charging_id")
                ->references("id")
                ->on("one_charging");
            $table->string("one_charging_sync_id")->nullable();
            $table->string("one_charging_code")->nullable();
            $table->string("one_charging_name")->nullable();
            $table->string("username")->unique();
            $table->string("password");
            $table->unsignedInteger("role_id")->index();
            $table
                ->foreign("role_id")
                ->references("id")
                ->on("roles");
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create("password_reset_tokens", function (Blueprint $table) {
            $table->string("email")->primary();
            $table->string("token");
            $table->timestamp("created_at")->nullable();
        });

        Schema::create("sessions", function (Blueprint $table) {
            $table->string("id")->primary();
            $table
                ->foreignId("user_id")
                ->nullable()
                ->index();
            $table->string("ip_address", 45)->nullable();
            $table->text("user_agent")->nullable();
            $table->longText("payload");
            $table->integer("last_activity")->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("users");
        Schema::dropIfExists("password_reset_tokens");
        Schema::dropIfExists("sessions");
    }
};
