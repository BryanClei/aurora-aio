<?php

namespace App\Http\Resources\Audit;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyAuditTrailResource extends JsonResource
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
            "module_type" => $this->module_type,
            "module_name" => $this->module_name,
            "module_id" => $this->module_id,
            "action" => $this->action,
            "action_by" => $this->action_by,
            "action_by_name" => $this->action_by_name,
            "log_info" => $this->log_info,
            "previous_data" => $this->previous_data,
            "new_data" => json_decode($this->new_data, true),
            "remarks" => $this->remarks,
            "ip_address" => $this->ip_address,
            "user_agent" => $this->user_agent,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "deleted_at" => $this->deleted_at,
        ];
    }
}
