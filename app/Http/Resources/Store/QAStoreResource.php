<?php

namespace App\Http\Resources\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QAStoreResource extends JsonResource
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
            "store_checklist" => QAStoreChecklistResource::collection(
                $this->store_checklist
            ),
            "region" => [
                "id" => optional($this->regions)->id,
                "name" => optional($this->regions)->name,
            ],
            "area" => [
                "id" => optional($this->areas)->id,
                "name" => optional($this->areas)->name,
            ],
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "deleted_at" => $this->deleted_at,
        ];
    }
}
