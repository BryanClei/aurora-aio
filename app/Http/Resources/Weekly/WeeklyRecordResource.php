<?php

namespace App\Http\Resources\Weekly;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Audit\SurveyAuditTrailResource;

class WeeklyRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "store_checklist_id" => $this->store_checklist_id,
            "week" => $this->week,
            "month" => $this->month,
            "year" => $this->year,
            "weekly_grade" => $this->weekly_grade,
            "is_auto_grade" => $this->is_auto_grade,
            "grade_source" => $this->grade_source,
            "graded_by" => $this->users,
            "status" => $this->status,
            "grade_notes" => $this->notes,
            "store_visit" => $this->store_visit,
            "condemned" => $this->condemned,
            "create_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "deleted_at" => $this->deleted_at,
            "for_approval_reason" => $this->for_approval_reason,
            // "weekly_response" => WeeklyQAResponseResource::collection(
            //     $this->weekly_response
            // ),
            "audit_trail" => SurveyAuditTrailResource::collection(
                $this->audit_trail
            ),
            "weekly_skipped" => $this->weekly_skipped,
        ];
    }
}
