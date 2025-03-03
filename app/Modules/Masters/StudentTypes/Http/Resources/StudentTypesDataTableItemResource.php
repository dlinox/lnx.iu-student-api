<?php

namespace App\Modules\StudentTypes\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentTypesDataTableItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'isActive' => $this->is_active,
        ];
    }
}
