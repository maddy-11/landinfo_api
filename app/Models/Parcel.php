<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parcel extends Model
{
    use HasFactory;

    protected $table = 'parcel';

    protected $fillable = [
        'OBJECTID',
        'Khassra_No',
        'Old_Khassr',
        'Massavi_No',
        'KhasraType',
        'Mauza_Name',
        'PC_Name',
        'KhassraId',
        'MozaId',
        'JamaBandiY',
        'HadBastNo',
        'MozaName_U',
        'KhataNo',
        'Status',
        'UC',
        'Tehsil',
        'District',
        'Division',
        'Province',
        'Shape_Leng',
        'Shape_Le_1',
        'Shape_Area',
        'geometry',
    ];

    protected function casts(): array
    {
        return [
            'geometry' => 'array',
            'Khassra_No' => 'decimal:2',
            'KhassraId' => 'decimal:2',
            'MozaId' => 'decimal:2',
            'HadBastNo' => 'decimal:2',
            'KhataNo' => 'decimal:2',
            'Shape_Leng' => 'decimal:8',
            'Shape_Le_1' => 'decimal:8',
            'Shape_Area' => 'decimal:8',
        ];
    }
}

