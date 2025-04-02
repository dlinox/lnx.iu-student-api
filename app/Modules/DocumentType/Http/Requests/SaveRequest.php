<?php

namespace App\Modules\DocumentType\Http\Requests;
use App\Http\Requests\BaseRequest;
class SaveRequest extends BaseRequest
{

    public function rules()
    {
        $id = $this->id ? $this->id : null;
        return [
            'name' => 'required|string|max:50|unique:document_types,name,' . $id,
            'is_enabled' => 'required|boolean',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Obligatorio',
            'name.max' => 'Máximo de 50 caracteres',
            'name.unique' => 'Ya existe un registro con este nombre',
            'is_enabled.required' => 'Obligatorio',
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'is_enabled' => $this->input('isEnabled', $this->is_enabled),
        ]);
    }

}
