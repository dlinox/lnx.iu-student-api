<?php

namespace App\Modules\EnrollmentGroup\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentGroupFormItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'isActive' => $this->is_active,
        ];
    }
}
