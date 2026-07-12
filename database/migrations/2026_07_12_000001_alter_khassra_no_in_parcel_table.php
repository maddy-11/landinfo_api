<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $type = DB::getSchemaBuilder()->getColumnType('parcel', 'Khassra_No');
        if ($type === 'decimal') {
            Schema::table('parcel', function (Blueprint $table) {
                $table->string('Khassra_No')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('parcel', function (Blueprint $table) {
            $table->decimal('Khassra_No', 15, 2)->nullable()->change();
        });
    }
};
