<?php

namespace App\Modules\Auth\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;

class SignUpRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'document_type_id' => 'required|exists:document_types,id',
            'document_number' => 'required|max:15|unique:people',
            'name' => 'required|max:50',
            'last_name_father' => 'nullable|max:50',
            'last_name_mother' => 'nullable|max:50',
            'gender' => 'nullable|in:1,2,0',
            'email' => 'required|email|max:80',
            'phone' => 'required|max:15',
            'student_type_id' => 'required|exists:student_types,id',
            'date_of_birth' => 'nullable',
            'is_enabled' => 'required|boolean',
        ];
    }

    public function messages()
    {
        return [
            'document_type_id.required' => 'Obligatorio',
            'document_type_id.exists' => 'No existe un registro con este identificador',
            'document_number.required' => 'Obligatorio',
            'document_number.max' => 'Máximo de 15 caracteres',
            'document_number.unique' => 'Ya existe un registro con este número de documento',
            'name.required' => 'Obligatorio',
            'name.max' => 'Máximo de 50 caracteres',
            'last_name_father.max' => 'Máximo de 50 caracteres',
            'last_name_mother.max' => 'Máximo de 50 caracteres',
            'gender.in' => 'Valor no permitido',
            'email.required' => 'Obligatorio',
            'email.email' => 'Formato de correo no válido',
            'email.max' => 'Máximo de 80 caracteres',
            'phone.required' => 'Obligatorio',
            'phone.max' => 'Máximo de 15 caracteres',
            'student_type_id.required' => 'Obligatorio',
            'student_type_id.exists' => 'No existe un registro con este identificador',
            'is_enabled.required' => 'Obligatorio',
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'document_type_id' => $this->input('documentTypeId', $this->document_type_id),
            'document_number' => $this->input('documentNumber', $this->document_number),
            'last_name_father' => $this->input('lastNameFather', $this->last_name_father),
            'last_name_mother' => $this->input('lastNameMother', $this->last_name_mother),
            'student_type_id' => $this->input('studentTypeId', $this->student_type_id),
            'date_of_birth' => $this->input('dateOfBirth', $this->date_of_birth),
            'is_enabled' => $this->input('isEnabled', $this->is_enabled),
        ]);
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = collect($validator->errors())->mapWithKeys(function ($messages, $field) {
            return [
                Str::camel($field) => $messages[0]
            ];
        });

        throw new HttpResponseException(
            response()->json(['errors' => $errors], 422)
        );
    }
}
