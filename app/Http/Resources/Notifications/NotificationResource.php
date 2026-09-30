<?php

namespace App\Http\Resources\Notifications;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Translates stored key + params according to the request locale.
 * Database payload shape: { key, params, type }.
 */
class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->data ?? [];
        $key = $data['key'] ?? $data['type'] ?? 'generic';
        $params = $data['params'] ?? [];

        return [
            'id' => $this->id,
            'type' => $data['type'] ?? $this->type,
            'title' => __("messages.notif.{$key}_title", $params),
            'body' => __("messages.notif.{$key}_body", $params),
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
