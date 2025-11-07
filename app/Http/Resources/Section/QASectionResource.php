<?php

namespace App\Http\Resources\Section;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Question\QAQuestionResource;

class QASectionResource extends JsonResource
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
            "checklist_id" => $this->checklist_id,
            "title" => $this->title,
            "description" => $this->description,
            "order_index" => $this->order_index,
            "category_id" => $this->category_id,
            "questions" => QAQuestionResource::collection($this->questions),
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "deleted_at" => $this->deleted_at,
        ];
    }
}
