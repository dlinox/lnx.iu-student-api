<?php

namespace App\Modules\Enrollment\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentFormItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'isActive' => $this->is_active,
        ];
    }
}
