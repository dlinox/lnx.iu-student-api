<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{

    protected $fillable = [
        'person_id',
        'student_type_id',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function person()
    {
        return $this->belongsTo('App\Modules\Core\Person\Models\Person');
    }

    public function enrollments()
    {
        return $this->hasMany('App\Modules\Enrollment\Models\Enrollment');
    }

    public  static function getStudentByUser($userModelId)
    {
        $student = self::select('students.id', 'students.student_type_id', 'students.is_enabled')
            ->where('students.id', $userModelId)
            ->first();

        return $student;
    }


    public static function registerItem($personId, $studentTypeId)
    {
        $item =  self::create([
            'person_id' => $personId,
            'student_type_id' => $studentTypeId,
        ]);
        return $item;
    }


    public static function updateItem($data)
    {
        $item =  self::find($data['id']);
        $item->update([
            'is_enabled' => $data['is_enabled'],
            'student_type_id' => $data['student_type_id'],
        ]);

        return $item;
    }

    public static function basicInformation($id)
    {

        $item = self::select(
            'students.id',
            'people.document_type_id as documentTypeId',
            'people.document_number as documentNumber',
            'people.name',
            'people.last_name_father as lastNameFather',
            'people.last_name_mother as lastNameMother',
            'people.gender',
            'people.date_of_birth as dateOfBirth',
            'people.phone',
        )
            ->join('people', 'students.person_id', '=', 'people.id')
            ->where('students.id', $id)
            ->first();

        return $item;
    }
}
