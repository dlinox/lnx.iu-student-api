<?php

namespace App\Modules\Auth\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AuthFormItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'isActive' => $this->is_active,
        ];
    }
}
