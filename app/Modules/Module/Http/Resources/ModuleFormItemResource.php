<?php

namespace App\Modules\Module\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ModuleFormItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'isActive' => $this->is_active,
        ];
    }
}
