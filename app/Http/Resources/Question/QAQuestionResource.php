<?php

namespace App\Http\Resources\Question;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QAQuestionResource extends JsonResource
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
            "section_id" => $this->section_id,
            "question_type" => $this->question_type,
            "question_text" => $this->question_text,
            "order_index" => $this->order_index,
            "options" => $this->options,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "deleted_at" => $this->deleted_at,
        ];
    }
}
