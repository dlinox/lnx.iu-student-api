<?php

namespace App\Modules\Student\Models;

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

    //un estudiante pertenece a una persona
    public function person()
    {
        return $this->belongsTo('App\Modules\Core\Person\Models\Person');
    }
    //un studinate puede estar matriculado en varios cursos
    public function enrollments()
    {
        return $this->hasMany('App\Modules\Enrollment\Models\Enrollment');
    }


    public static function registerItem($data)
    {
        $item =  self::create([
            'person_id' => $data['person_id'],
            'student_type_id' => $data['student_type_id'],
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
}
