<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('one_rdf_users', function (Blueprint $table) {
            $table->id();
            $table->string("id_prefix");
            $table->string("id_no");
            $table->string("username")->unique();
            $table->string("password");
            $table->string("first_name");
            $table->string("middle_name")->nullable();
            $table->string("last_name");
            $table->string("suffix")->nullable();
            $table->timestamp("synced_at")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('one_rdf_user');
    }
};
