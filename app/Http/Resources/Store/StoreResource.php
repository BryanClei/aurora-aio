<?php

namespace App\Http\Resources\Store;

use Illuminate\Http\Request;
use App\Http\Resources\Area\AreaResource;
use App\Http\Resources\Region\RegionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
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
            "region" => new RegionResource($this->regions),
            "area" => new AreaResource($this->areas),
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "deleted_at" => $this->deleted_at,
        ];
    }
}
