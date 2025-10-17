<?php

namespace App\Http\Resources\Weekly;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Weekly\WeeklyQAStoreChecklistResource;

class WeeklyQAStoreResource extends JsonResource
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
            "code" => $this->code,
            "name" => $this->name,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "deleted_at" => $this->deleted_at,
            "store_checklist" => WeeklyQAStoreChecklistResource::collection(
                $this->store_checklist
            ),
        ];
    }
}
