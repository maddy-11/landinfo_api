<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('karam', function (Blueprint $table) {
            $table->id();
            $table->integer('OBJECTID_1')->nullable();
            $table->string('Karam')->nullable();
            $table->string('Karam_Urdu')->nullable();
            $table->string('Khasra_No_')->nullable();
            $table->string('Massavi_No')->nullable();
            $table->string('Hadbast_No')->nullable();
            $table->string('Mauza_Name')->nullable();
            $table->string('PC_Name')->nullable();
            $table->string('QH_Name')->nullable();
            $table->string('UC_Name')->nullable();
            $table->string('Tehsil_Nam')->nullable();
            $table->string('Distt_Name')->nullable();
            $table->string('Division_N')->nullable();
            $table->string('Province_N')->nullable();
            $table->string('Country_Na')->nullable();
            $table->text('Remarks')->nullable();
            $table->decimal('SHAPE_Leng', 15, 8)->nullable();
            $table->decimal('Shape_Le_1', 15, 8)->nullable();
            $table->integer('Cal')->nullable();
            $table->string('Status')->nullable();
            $table->integer('testing')->nullable();
            $table->json('geometry');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karam');
    }
};

