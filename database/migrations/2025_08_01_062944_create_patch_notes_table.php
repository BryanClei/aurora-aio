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
        Schema::create("patch_notes", function (Blueprint $table) {
            $table->id();
            $table->string("title");
            $table->text("description");
            $table->string("version")->nullable();
            $table->string("filename")->nullable();
            $table->string("filepath")->nullable();
            $table
                ->enum("type", [
                    "feature",
                    "bugfix",
                    "security",
                    "performance",
                    "breaking",
                ])
                ->default("feature");
            $table->boolean("is_published")->default(false);
            $table->timestamp("published_at")->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index("title");
            $table->index(["is_published", "published_at"]);
            $table->index("version");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("patch_notes");
    }
};
