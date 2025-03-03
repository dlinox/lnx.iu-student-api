<?php

namespace App\Modules\Masters\DocumentType\Models;

use App\Traits\HasEnabledState;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasEnabledState;

    protected $fillable = [
        'name',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];
    public $timestamps = false;

    public static function getItemsForSelect()
    {
        $documentTypes = self::select('id as value', 'name as title')
            ->enabled()->get();
        return $documentTypes;
    }
}
