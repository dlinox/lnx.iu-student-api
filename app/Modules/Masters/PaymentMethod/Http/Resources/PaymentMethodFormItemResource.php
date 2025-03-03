<?php

namespace App\Modules\PaymentMethod\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodFormItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'isActive' => $this->is_active,
        ];
    }
}
