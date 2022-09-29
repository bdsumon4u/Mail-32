<?php
/**
 * Concord CRM - https://www.concordcrm.com
 *
 * @version   1.0.7
 *
 * @link      Releases - https://www.concordcrm.com/releases
 * @link      Terms Of Service - https://www.concordcrm.com/terms
 *
 * @copyright Copyright (c) 2022-2022 KONKORD DIGITAL
 */

namespace App\Http\Resources;

use App\Enums\ConnectionType;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\EmailAccount */
class EmailAccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return array_merge(parent::toArray($request), [
            'email' => $this->email,
            'connection_type' => $this->connection_type,
            'requires_auth' => $this->requires_auth,
            'sync_state_comment' => $this->sync_state_comment,
            'is_initial_sync_performed' => $this->isInitialSyncPerformed(),
            'is_sync_disabled' => $this->isSyncDisabled(),
            'is_sync_stopped' => $this->isSyncStoppedBySystem(),
            'type' => $this->type,
            'is_shared' => $this->isShared(),
            'is_personal' => $this->isPersonal(),
            'formatted_from_name_header' => $this->formatted_from_name_header,
            'create_contact' => $this->create_contact,
            'folders' => EmailAccountFolderResource::collection($this->folders),
            'folders_tree' => $this->folders->createTree($request), // @phpstan-ignore-line
            'active_folders' => EmailAccountFolderResource::collection($this->folders->active()), // @phpstan-ignore-line
            'active_folders_tree' => $this->folders->createTreeFromActive($request), // @phpstan-ignore-line
            'sent_folder' => new EmailAccountFolderResource($this->whenLoaded('sentFolder')),
            'trash_folder' => new EmailAccountFolderResource($this->whenLoaded('trashFolder')),
            'sent_folder_id' => $this->sent_folder_id,
            'trash_folder_id' => $this->trash_folder_id,
            $this->mergeWhen($this->isShared(), [
                'from_name_header' => $this->from_name_header,
            ]),
            $this->mergeWhen($this->connection_type === ConnectionType::Imap, [
                'username' => $this->username,
                'imap_server' => $this->imap_server,
                'imap_port' => $this->imap_port,
                'imap_encryption' => $this->imap_encryption,
                'smtp_server' => $this->smtp_server,
                'smtp_port' => $this->smtp_port,
                'smtp_encryption' => $this->smtp_encryption,
                'validate_cert' => $this->validate_cert,
            ]),
        ]);
    }
}
