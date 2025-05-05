<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{

    protected $fillable = [
        'code',
        'document_type_id',
        'document_number',
        'name',
        'last_name_father',
        'last_name_mother',
        'gender_id',
        'date_of_birth',
        'address',
        'phone',
        'email',
        'location_id',
        'country_id',
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

    public static function generateCode()
    {
        $year = date('Y');

        $correlative = self::where('code', 'like', $year . '%')->max('code');
        if ($correlative) {
            $correlative = (int) substr($correlative, 4);
            $correlative++;
        } else {
            $correlative = 1;
        }
        $correlative = str_pad($correlative, 4, '0', STR_PAD_LEFT);
        $correlative = $year . $correlative;
        return $correlative;
    }

    public static function registerItem($data)
    {

        $code = self::generateCode();

        $item =  self::create([
            'student_type_id' => $data['studentTypeId'],
            'code' => $code,
            'document_type_id' => $data['documentTypeId'],
            'document_number' => $data['documentNumber'],
            'name' => $data['name'],
            'last_name_father' => $data['lastNameFather'] ?? '',
            'last_name_mother' => $data['lastNameMother'] ?? '',
            'gender_id' => $data['gender'],
            'date_of_birth' => $data['dateOfBirth'],
            'address' => $data['address'] ?? '',
            'phone' => $data['phone'],
            'email' => $data['email'],
        ]);

        return $item;
    }

    public  static function getStudentByUser($userModelId)
    {
        $student = self::select('students.id', 'students.student_type_id', 'students.is_enabled')
            ->where('students.id', $userModelId)
            ->first();

        return $student;
    }

    public function updatePersonalDataItem($data)
    {
        $this->update([
            'document_type_id' => $data['documentTypeId'] ?? $this->document_type_id,
            'document_number' => $data['documentNumber'] ?? $this->document_number,
            'name' => $data['name'] ?? $this->name,
            'last_name_father' => $data['lastNameFather'] ?? $this->last_name_father,
            'last_name_mother' => $data['lastNameMother'] ?? $this->last_name_mother,
            'gender_id' => $data['gender'] ?? $this->gender,
            'date_of_birth' => $data['dateOfBirth'] ?? $this->date_of_birth,
            'address' => $data['address'] ?? $this->address,
            'phone' => $data['phone'] ?? $this->phone,
            'email' => $data['email'] ?? $this->email,
        ]);

        return $this;
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
            'students.document_type_id as documentTypeId',
            'students.document_number as documentNumber',
            'students.name',
            'students.last_name_father as lastNameFather',
            'students.last_name_mother as lastNameMother',
            'students.gender_id as gender',
            'students.date_of_birth as dateOfBirth',
            'students.phone',
        )
            ->where('students.id', $id)
            ->first();

        return $item;
    }
}
