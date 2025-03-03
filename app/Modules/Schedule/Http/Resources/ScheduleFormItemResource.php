<?php

namespace App\Modules\Schedule\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleFormItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'isActive' => $this->is_active,
        ];
    }
}
