<?php

namespace App\Models;

use App\Traits\HasDataTable;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasDataTable;

    protected $fillable = [
        'code',
        'document_type_id',
        'document_number',
        'name',
        'last_name_father',
        'last_name_mother',
        'gender',
        'date_of_birth',
        'address',
        'phone',
        'email',
        'location_id',
        'country_id',
    ];

    protected $casts = [];


    //generar codigo de persona año + correlativo 20250001
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
            'code' => $code,
            'document_type_id' => $data['documentTypeId'],
            'document_number' => $data['documentNumber'],
            'name' => $data['name'],
            'last_name_father' => $data['lastNameFather'] ?? '',
            'last_name_mother' => $data['lastNameMother'] ?? '',
            'gender' => $data['gender'],
            'date_of_birth' => $data['dateOfBirth'],
            'address' => $data['address'] ?? '',
            'phone' => $data['phone'],
            'email' => $data['email'],
        ]);

        return $item;
    }

    public function updateItem($data)
    {
        $this->update([
            'document_type_id' => $data['documentTypeId'] ?? $this->document_type_id,
            'document_number' => $data['documentNumber'] ?? $this->document_number,
            'name' => $data['name'] ?? $this->name,
            'last_name_father' => $data['lastNameFather'] ?? $this->last_name_father,
            'last_name_mother' => $data['lastNameMother'] ?? $this->last_name_mother,
            'gender' => $data['gender'] ?? $this->gender,
            'date_of_birth' => $data['dateOfBirth'] ?? $this->date_of_birth,
            'address' => $data['address'] ?? $this->address,
            'phone' => $data['phone'] ?? $this->phone,
            'email' => $data['email'] ?? $this->email,
        ]);

        return $this;
    }

    public static function getByStudentId($studentId)
    {
        $person = self::select('people.*')
            ->join('students', 'people.id', '=', 'students.person_id')
            ->where('students.id', $studentId)
            ->first();

        return $person;
    }

}
