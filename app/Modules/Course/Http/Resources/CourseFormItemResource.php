<?php

namespace App\Modules\Course\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseFormItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'isActive' => $this->is_active,
        ];
    }
}
