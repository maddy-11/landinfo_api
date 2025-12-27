<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcel', function (Blueprint $table) {
            $table->id();
            $table->integer('OBJECTID')->nullable();
            $table->decimal('Khassra_No', 15, 2)->nullable();
            $table->string('Old_Khassr')->nullable();
            $table->string('Massavi_No')->nullable();
            $table->string('KhasraType')->nullable();
            $table->string('Mauza_Name')->nullable();
            $table->string('PC_Name')->nullable();
            $table->decimal('KhassraId', 15, 2)->nullable();
            $table->decimal('MozaId', 10, 2)->nullable();
            $table->string('JamaBandiY')->nullable();
            $table->decimal('HadBastNo', 10, 2)->nullable();
            $table->string('MozaName_U')->nullable();
            $table->decimal('KhataNo', 10, 2)->nullable();
            $table->string('Status')->nullable();
            $table->string('UC')->nullable();
            $table->string('Tehsil')->nullable();
            $table->string('District')->nullable();
            $table->string('Division')->nullable();
            $table->string('Province')->nullable();
            $table->decimal('Shape_Leng', 15, 8)->nullable();
            $table->decimal('Shape_Le_1', 15, 8)->nullable();
            $table->decimal('Shape_Area', 15, 8)->nullable();
            $table->json('geometry');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcel');
    }
};

