<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('control_point', function (Blueprint $table) {
            $table->id();
            // Which control tier this monument belongs to (1, 2, …). Indexed
            // because every lookup is "give me level N".
            $table->unsignedTinyInteger('level')->index();
            $table->string('bm_code')->nullable()->index();
            $table->string('lims_id')->nullable();
            $table->string('District')->nullable()->index();
            $table->string('Tehsil')->nullable()->index();
            // Raw construction status from the source file ("constructed",
            // "Not_Start", …) plus a normalised form the dashboards can group on.
            $table->string('status_raw')->nullable();
            $table->string('status')->nullable()->index();
            $table->string('execution')->nullable();
            $table->string('within_10k')->nullable();
            $table->string('distance')->nullable();
            $table->string('alt_location')->nullable();
            $table->decimal('original_x', 13, 8)->nullable();
            $table->decimal('original_y', 13, 8)->nullable();
            $table->decimal('altered_x', 13, 8)->nullable();
            $table->decimal('altered_y', 13, 8)->nullable();
            $table->text('remarks')->nullable();
            $table->json('geometry');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_point');
    }
};
