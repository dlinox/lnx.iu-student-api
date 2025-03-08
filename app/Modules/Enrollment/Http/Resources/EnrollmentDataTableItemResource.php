<?php

namespace App\Modules\Enrollment\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentDataTableItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'groupId' => $this->groupId,
            'group' => $this->group,
            'enrollmentStatus' => $this->enrollmentStatus,
            'module' => $this->module,
            'modality' => $this->modality,
            'laboratory' => $this->laboratory,
            'teacher' => $this->teacher,
            'period' => ucfirst($this->period),
            'course' => $this->course,
            'code' => $this->code,
            'credits' => $this->credits,
            'hoursPractice' => $this->hoursPractice,
            'hoursTheory' => $this->hoursTheory,
            'area' => $this->area,
        ];
    }
}
