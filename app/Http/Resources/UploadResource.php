<?php

namespace App\Http\Resources;

use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Class UploadResource
 *
 * @mixin Upload
 */
class UploadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
//            'user_id' => $this->user_id,
            'filename' => $this->filename,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'path' => $this->path,
            'disk' => $this->disk,
            'provider' => $this->provider,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'url' => $this->when($this->path, function () {
                return $this->getFileUrl();
            }),
        ];
    }

    /**
     * Get the URL for the uploaded file.
     *
     * @return string|null
     */
    protected function getFileUrl(): ?string
    {
        if ($this->provider === 'cloudinary') {
            return $this->path;
        }

        return $this->disk === 'local'
            ? asset('storage/' . $this->path)
            : Storage::disk($this->disk)->url($this->path);
    }
}
