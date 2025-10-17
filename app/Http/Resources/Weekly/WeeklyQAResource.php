<?php

namespace App\Http\Resources\Weekly;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Weekly\WeeklyQAStoreResource;

class WeeklyQAResource extends JsonResource
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
            "name" => $this->name,
            "region" => [
                "id" => optional($this->region)->id,
                "name" => optional($this->region)->name,
            ],
            "area_head" => [
                "id" => optional($this->area_head)->id,
                "full_name" => trim(
                    collect([
                        $this->area_head->first_name,
                        $this->area_head->middle_name,
                        $this->area_head->last_name,
                        $this->area_head->suffix,
                    ])
                        ->filter()
                        ->implode(" ")
                ),
                "user_status" => $this->area_head->deleted_at
                    ? "inactive"
                    : "active",
            ],
            "store" => WeeklyQAStoreResource::collection($this->store) ?? null,
        ];
    }
}
