<?php

namespace App\Modules\Period\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PeriodFormItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'isActive' => $this->is_active,
        ];
    }
}
