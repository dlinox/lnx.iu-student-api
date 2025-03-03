<?php

namespace App\Modules\Core\Person\Models;

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

        if (isset($data['code'])) {
            $code = $data['code'];
        } else {
            $code = self::generateCode();
        }

        $item =  self::create([
            'code' => $code,
            'document_type_id' => $data['document_type_id'],
            'document_number' => $data['document_number'],
            'name' => $data['name'],
            'last_name_father' => $data['last_name_father'],
            'last_name_mother' => $data['last_name_mother'],
            'gender' => $data['gender'],
            'date_of_birth' => $data['date_of_birth'],
            'address' => $data['address'] ?? '',
            'phone' => $data['phone'],
            'email' => $data['email'],
        ]);

        return $item;
    }

    public static function updateItem($data)
    {
        $item =  self::find($data['person_id']);
        $item->update([
            'code' => $data['code'],
            'document_type_id' => $data['document_type_id'],
            'document_number' => $data['document_number'],
            'name' => $data['name'],
            'last_name_father' => $data['last_name_father'],
            'last_name_mother' => $data['last_name_mother'],
            'gender' => $data['gender'],
            'date_of_birth' => $data['date_of_birth'],
            'address' => $data['address'],
            'phone' => $data['phone'],
            'email' => $data['email'],
        ]);

        return $item;
    }
}
