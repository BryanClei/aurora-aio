<?php

namespace App\Http\Resources\Weekly;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Weekly\WeeklyStaffOnDutyResource;

class WeeklyQAResponseResource extends JsonResource
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
            "response_id" => $this->response_id,
            "weekly_record_id" => $this->weekly_record_id,
            "section_id" => $this->section_id,
            "section_title" => $this->section_title,
            "section_score" => $this->section_score,
            "section_order_index" => $this->section_order_index,
            "question_id" => $this->question_id,
            "question_text" => $this->question_text,
            "question_type" => $this->question_type,
            "score_rating" => $this->score_rating,
            "answer_text" => $this->answer_text,
            "selected_options" => json_decode($this->selected_options, true),
            "store_visit" => $this->store_visit,
            "expired" => $this->expired,
            "condemned" => $this->condemned,
            "store_duty" => new WeeklyStaffOnDutyResource($this->staff_on_duty),
            "good_points" => $this->good_points,
            "notes" => $this->notes,
            "score" => $this->score,
            "attachment" => $this->attachment,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "deleted_at" => $this->deleted_at,
        ];
    }
}
