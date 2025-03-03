<?php

namespace App\Modules\Student\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentDataTableItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'isActive' => $this->is_active,
        ];
    }
}
