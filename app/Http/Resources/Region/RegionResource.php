<?php

namespace App\Http\Resources\Region;

use Illuminate\Http\Request;
use App\Http\Resources\Area\AreaResource;
use Illuminate\Http\Resources\Json\JsonResource;

class RegionResource extends JsonResource
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
            "region_head_id" => $this->region_head_id,
            "region_head" => [
                "id" => $this->region_head_id,
                "full_name" => trim(
                    collect([
                        $this->region_head->first_name,
                        $this->region_head->middle_name,
                        $this->region_head->last_name,
                        $this->region_head->suffix,
                    ])
                        ->filter()
                        ->implode(" ")
                ),
                "user_status" => $this->region_head->deleted_at
                    ? "inactive"
                    : "active",
            ],
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "deleted_at" => $this->deleted_at,
            "areas" => new AreaResource($this->areas) ?? null,
        ];
    }
}
