<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->due_date,
            'assigned_to' => $this->assigned_to,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // 'assigned_user' => new AssignedUserResource($this->whenLoaded('assignedUser')),
            'assigned_user' => $this->whenLoaded('assigned_user', function () {
                return $this->assigned_user->pluck('name');
            }),
            'creator' => $this->whenLoaded('creator', function () {
        return $this->creator ? $this->creator->id : null;
           }),
            // 'dependencies' => DependencyResource::collection($this->whenLoaded('dependencies')),
            'dependencies' => $this->whenLoaded('dependencies', function () {
                return $this->dependencies->pluck('title');
            }),
        ];    }
}
