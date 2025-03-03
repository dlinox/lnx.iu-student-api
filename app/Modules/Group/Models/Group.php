<?php

namespace App\Modules\Group\Models;

use App\Traits\HasDataTable;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasDataTable;

    protected $fillable = ['is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected $hidden = ['created_at', 'updated_at'];

    static $searchColumns = [];
}
