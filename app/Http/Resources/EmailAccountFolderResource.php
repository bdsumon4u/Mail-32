<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\EmailAccountFolder */
class EmailAccountFolderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'email_account_id' => $this->email_account_id,
            'remote_id' => $this->remote_id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'syncable' => $this->syncable,
            'selectable' => $this->selectable,
            'unread_count' => (int) $this->unread_count ?: 0, // @phpstan-ignore-line
            'type' => $this->type,
        ];
    }
}
