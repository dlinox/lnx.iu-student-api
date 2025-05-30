<?php

namespace App\Modules\Group\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GroupDataTableItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'isActive' => $this->is_active,
        ];
    }
}
