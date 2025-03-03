<?php

namespace App\Modules\DocumentType\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DocumentTypeDataTableItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'isActive' => $this->is_active,
        ];
    }
}
