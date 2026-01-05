<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AfradRegister extends Model
{
    use HasFactory;

    protected $table = 'AfradRegister';
    protected $primaryKey = 'PersonId';
    public $timestamps = false;

    protected $fillable = [
        'PersonId',
        'PersonTypeId',
        'FamilyId',
        'QoamId',
        'FamilyHead',
        'FamilyNo',
        'FamilyLevel',
        'ParentId',
        'SeqNo',
        'PersonName',
        'Relation',
        'Fathername',
        'MotherName',
        'Gender',
        'PersonDied',
        'PersonCategoryId',
        'Mozald',
        'CNIC',
        'DateOfBirth',
        'Sakna',
        'Address',
        'PicName',
        'Age',
        'FamilyNo_New',
        'AfradRegisterGUID',
        'OldPersonId',
        'OldParentId',
        'InsertUserId',
        'InsertLoginName',
        'InsertDate',
        'UpdateUserId',
        'UpdateLoginName',
        'UpdateDate',
        'RecStatus',
        'RahinId',
        'PersonFamilyStatusId',
        'PersonId_Old',
    ];
}

