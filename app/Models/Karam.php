<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Karam extends Model
{
    use HasFactory;

    protected $table = 'karam';

    protected $fillable = [
        'OBJECTID_1',
        'Karam',
        'Karam_Urdu',
        'Khasra_No_',
        'Massavi_No',
        'Hadbast_No',
        'Mauza_Name',
        'PC_Name',
        'QH_Name',
        'UC_Name',
        'Tehsil_Nam',
        'Distt_Name',
        'Division_N',
        'Province_N',
        'Country_Na',
        'Remarks',
        'SHAPE_Leng',
        'Shape_Le_1',
        'Cal',
        'Status',
        'testing',
        'geometry',
    ];

    protected function casts(): array
    {
        return [
            'geometry' => 'array',
            'SHAPE_Leng' => 'decimal:8',
            'Shape_Le_1' => 'decimal:8',
        ];
    }
}

