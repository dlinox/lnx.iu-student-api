<?php

namespace App\Traits;

trait HasEnabledState
{
    public function scopeEnabled($query)
    {
        $tableName = $this->getModel()->getTable();
        return $query->where($tableName . '.is_enabled', true);
    }

    public function scopeDisabled($query)
    {
        $tableName = $this->getModel()->getTable();
        return $query->where($tableName . '.is_enabled', false);
    }

    public function enable(): void
    {
        $this->is_enabled = true;
        $this->save();
    }

    public function disable(): void
    {
        $this->is_enabled = false;
        $this->save();
    }
}
