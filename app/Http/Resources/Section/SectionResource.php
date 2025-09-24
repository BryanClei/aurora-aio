<?php

namespace App\Http\Resources\Section;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
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
            // "questions" => QuestionResource::collection(
            //     $this->whenLoaded("questions")
            // ),
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "deleted_at" => $this->deleted_at,
        ];
    }
}
