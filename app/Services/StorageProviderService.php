<?php

namespace App\Services;

use App\Enums\StorageProviderEnum;
use App\Models\Upload;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Config, Storage};
use Illuminate\Support\Str;
use Intervention\Image\Interfaces\ImageInterface;
use InvalidArgumentException;
use Intervention\Image\Laravel\Facades\Image;

class StorageProviderService
{
    /**
     * Upload a file to the specified storage provider.
     *
     * @param UploadedFile $file The file to upload
     * @param string $path The path to store the file
     * @param StorageProviderEnum $provider The storage provider to use
     * @param int $userId The ID of the user uploading the file
     * @return Upload|null The created Upload model instance or null if upload failed
     */
    public function uploadFile(UploadedFile $file, string $path, StorageProviderEnum $provider, int $userId): ?Upload
    {
        $uploadMethod = 'uploadTo' . Str::studly($provider->value);

        if (method_exists($this, $uploadMethod)) {
            return $this->$uploadMethod($file, $path, $userId);
        }

        throw new InvalidArgumentException("Unsupported storage provider: {$provider->value}");
    }

    /**
     * Upload a file to the local storage.
     *
     * @param UploadedFile $file The file to upload
     * @param string $path The path to store the file
     * @param int $userId The ID of the user uploading the file
     * @return Upload|null The created Upload model instance or null if upload failed
     */
    protected function uploadToLocal(UploadedFile $file, string $path, int $userId): ?Upload
    {
        $filename = $this->generateUniqueFilename($file);
        $fullPath = $file->storeAs($path, $filename, 'local');

        if ($fullPath && $this->shouldWatermark($file)) {
            $this->addWatermark(storage_path('app/' . $fullPath));
        }

        return $fullPath ? $this->createUploadRecord($file, Storage::url($fullPath), 'local', StorageProviderEnum::LOCAL, $userId) : null;
    }

    /**
     * Upload a file to Amazon S3.
     *
     * @param UploadedFile $file The file to upload
     * @param string $path The path to store the file
     * @param int $userId The ID of the user uploading the file
     * @return Upload|null The created Upload model instance or null if upload failed
     */
    protected function uploadToS3(UploadedFile $file, string $path, int $userId): ?Upload
    {
        $filename = $this->generateUniqueFilename($file);
        $fullPath = $path . '/' . $filename;

        if ($this->shouldWatermark($file)) {
            $image = $this->addWatermark($file->getRealPath());
            $result = Storage::disk('s3')->put($fullPath, $image->encode());
        } else {
            $result = Storage::disk('s3')->put($fullPath, file_get_contents($file->getRealPath()));
        }

        return $result ? $this->createUploadRecord($file, Storage::disk('s3')->url($fullPath), 's3', StorageProviderEnum::S3, $userId) : null;
    }

    /**
     * Upload a file to Google Cloud Storage.
     *
     * @param UploadedFile $file The file to upload
     * @param string $path The path to store the file
     * @param int $userId The ID of the user uploading the file
     * @return Upload|null The created Upload model instance or null if upload failed
     */
    protected function uploadToGoogle(UploadedFile $file, string $path, int $userId): ?Upload
    {
        $filename = $this->generateUniqueFilename($file);
        $fullPath = $path . '/' . $filename;

        if ($this->shouldWatermark($file)) {
            $image = $this->addWatermark($file->getRealPath());
            $result = Storage::disk('gcs')->put($fullPath, $image->encode());
        } else {
            $result = Storage::disk('gcs')->put($fullPath, file_get_contents($file->getRealPath()));
        }

        return $result ? $this->createUploadRecord($file, Storage::disk('gcs')->url($fullPath), 'gcs', StorageProviderEnum::GOOGLE, $userId) : null;
    }

    /**
     * Upload a file to Cloudinary with optimization.
     *
     * @param UploadedFile $file The file to upload
     * @param string $path The path to store the file
     * @param int $userId The ID of the user uploading the file
     * @return Upload|null The created Upload model instance or null if upload failed
     */
    protected function uploadToCloudinary(UploadedFile $file, string $path, int $userId): ?Upload
    {
        $filename = $this->generateUniqueFilename($file);
        $publicId = $path . '/' . pathinfo($filename, PATHINFO_FILENAME);

        $resourceType = $this->getResourceType($file);
        $optimizationOptions = $this->getOptimizationOptions($resourceType);

        $uploadOptions = array_merge([
            'folder' => $path,
            'public_id' => pathinfo($filename, PATHINFO_FILENAME),
            'resource_type' => $resourceType,
        ], $optimizationOptions);
//        if ($this->shouldWatermark($file)) {
//            $image = $this->addWatermark($file->getRealPath());
//            $result = Cloudinary::upload($image->encode()->toString(), $uploadOptions);
//        } else {
//            $result = Cloudinary::upload($file->getRealPath(), $uploadOptions);
//        }
        $result = Cloudinary::upload($file->getRealPath(), $uploadOptions);

        if (!$result || $result->getSecurePath() === null) {
            return null;
        }

        return $this->createUploadRecord($file, $result->getSecurePath(), 'cloudinary', StorageProviderEnum::CLOUDINARY, $userId, [
            'asset_id' => $result->getAssetId(),
            'public_id' => $result->getPublicId(),
            'version' => $result->getVersion(),
            'version_id' => $result->getVersionId(),
            'signature' => $result->getSignature(),
            'width' => $result->getWidth(),
            'height' => $result->getHeight(),
            'extension' => $result->getExtension(),
            'file_type' => $result->getFileType(),
            'created_at' => $result->getTimeUploaded(),
            'size' => $result->getSize(),
            'placeholder' => $result->getPlaceHolder(),
            'url' => $result->getPath(),
            'secure_url' => $result->getSecurePath(),
        ]);
    }

    /**
     * Upload a file to FTP server.
     *
     * @param UploadedFile $file The file to upload
     * @param string $path The path to store the file
     * @param int $userId The ID of the user uploading the file
     * @return Upload|null The created Upload model instance or null if upload failed
     */
    protected function uploadToFtp(UploadedFile $file, string $path, int $userId): ?Upload
    {
        $filename = $this->generateUniqueFilename($file);
        $fullPath = $file->storeAs($path, $filename, 'ftp');

        return $fullPath ? $this->createUploadRecord($file, $fullPath, 'ftp', StorageProviderEnum::FTP, $userId) : null;
    }

    /**
     * Upload a file to SFTP server.
     *
     * @param UploadedFile $file The file to upload
     * @param string $path The path to store the file
     * @param int $userId The ID of the user uploading the file
     * @return Upload|null The created Upload model instance or null if upload failed
     */
    protected function uploadToSftp(UploadedFile $file, string $path, int $userId): ?Upload
    {
        $filename = $this->generateUniqueFilename($file);
        $fullPath = $file->storeAs($path, $filename, 'sftp');

        return $fullPath ? $this->createUploadRecord($file, $fullPath, 'sftp', StorageProviderEnum::SFTP, $userId) : null;
    }

    /**
     * Upload a file to Dropbox.
     *
     * @param UploadedFile $file The file to upload
     * @param string $path The path to store the file
     * @param int $userId The ID of the user uploading the file
     * @return Upload|null The created Upload model instance or null if upload failed
     */
    protected function uploadToDropbox(UploadedFile $file, string $path, int $userId): ?Upload
    {
        $filename = $this->generateUniqueFilename($file);
        $fullPath = $file->storeAs($path, $filename, 'dropbox');

        return $fullPath ? $this->createUploadRecord($file, $fullPath, 'dropbox', StorageProviderEnum::DROPBOX, $userId) : null;
    }

    /**
     * Generate a unique filename for the uploaded file.
     *
     * @param UploadedFile $file The uploaded file
     * @return string The generated unique filename
     */
    protected function generateUniqueFilename(UploadedFile $file): string
    {
        return Str::uuid() . '.' . $file->getClientOriginalExtension();
    }

    /**
     * Create an Upload record in the database.
     *
     * @param UploadedFile $file The uploaded file
     * @param string $path The full path where the file is stored
     * @param string $disk The storage disk used
     * @param StorageProviderEnum $provider The storage provider used
     * @param int $userId The ID of the user who uploaded the file
     * @param array $meta Additional metadata to store
     * @return Upload The created Upload model instance
     */
    protected function createUploadRecord(UploadedFile $file, string $path, string $disk, StorageProviderEnum $provider, int $userId, array $meta = []): Upload
    {
        return Upload::create([
            'user_id' => $userId,
            'filename' => basename($path),
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'path' => $path,
            'disk' => $disk,
            'provider' => $provider->value,
            'meta' => $meta,
        ]);
    }

    /**
     * Determine the resource type based on the file's MIME type.
     *
     * @param UploadedFile $file
     * @return string
     */
    private function getResourceType(UploadedFile $file): string
    {
        $mimeType = $file->getMimeType();
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } else {
            return 'raw';
        }
    }

    /**
     * Get optimization options based on a resource type.
     *
     * @param string $resourceType
     * @return array
     */
    private function getOptimizationOptions(string $resourceType): array
    {
        return match ($resourceType) {
            'image' => [
                'quality' => 'auto',
                'fetch_format' => 'auto',
                'crop' => 'limit',
                'width' => 2000,
                'height' => 2000,
            ],
            'video' => [
                'quality' => 'auto',
                'fetch_format' => 'auto',
                'width' => 1920,
                'height' => 1080,
                'crop' => 'limit',
                'resource_type' => 'video',
            ],
            default => [],
        };
    }

    /**
     * Check if watermarking is enabled and if the file is an image.
     *
     * @param UploadedFile $file
     * @return bool
     */
    private function shouldWatermark(UploadedFile $file): bool
    {
        return Config::get('filesystems.watermark_enabled', false) && $this->isImage($file);
    }

    /**
     * Check if the file is an image.
     *
     * @param UploadedFile $file
     * @return bool
     */
    private function isImage(UploadedFile $file): bool
    {
        return str_starts_with($file->getMimeType(), 'image/');
    }

    /**
     * Add watermark to the image.
     *
     * @param string $imagePath
     * @return ImageInterface
     */
    private function addWatermark(string $imagePath): ImageInterface
    {
        $image = Image::read($imagePath);
        $watermarkPath = Config::get('filesystems.watermark_path', public_path('images/watermark.png'));
        $image->place($watermarkPath, 'bottom-right', 10, 10);

        return $image;
    }


    /**
     * Delete a file from the specified storage provider.
     *
     * @param Upload $upload The Upload model instance representing the file to delete
     * @return bool True if the file was successfully deleted, false otherwise
     */
    public function deleteFile(Upload $upload, StorageProviderEnum $provider): bool
    {
        $deleteMethod = 'deleteFrom' . Str::studly($provider->value);

        if (method_exists($this, $deleteMethod)) {
            return $this->$deleteMethod($upload);
        }

        throw new InvalidArgumentException("Unsupported storage provider: {$upload->provider}");
    }

    /**
     * Delete a file from local storage.
     *
     * @param Upload $upload The Upload model instance representing the file to delete
     * @return bool True if the file was successfully deleted, false otherwise
     */
    protected function deleteFromLocal(Upload $upload): bool
    {
        return Storage::disk('local')->delete($upload->path);
    }

    /**
     * Delete a file from Amazon S3.
     *
     * @param Upload $upload The Upload model instance representing the file to delete
     * @return bool True if the file was successfully deleted, false otherwise
     */
    protected function deleteFromS3(Upload $upload): bool
    {
        return Storage::disk('s3')->delete($upload->path);
    }

    /**
     * Delete a file from Google Cloud Storage.
     *
     * @param Upload $upload The Upload model instance representing the file to delete
     * @return bool True if the file was successfully deleted, false otherwise
     */
    protected function deleteFromGoogle(Upload $upload): bool
    {
        return Storage::disk('gcs')->delete($upload->path);
    }

    /**
     * Delete a file from Cloudinary.
     *
     * @param Upload $upload The Upload model instance representing the file to delete
     * @return bool True if the file was successfully deleted, false otherwise
     */
    protected function deleteFromCloudinary(Upload $upload): bool
    {
        $publicId = $upload->meta['public_id'] ?? null;
        if (!$publicId) {
            return false;
        }

        $result = Cloudinary::destroy($publicId);
        $upload->delete();
        return $result === 'ok';
    }

    /**
     * Delete a file from FTP server.
     *
     * @param Upload $upload The Upload model instance representing the file to delete
     * @return bool True if the file was successfully deleted, false otherwise
     */
    protected function deleteFromFtp(Upload $upload): bool
    {
        return Storage::disk('ftp')->delete($upload->path);
    }

    /**
     * Delete a file from SFTP server.
     *
     * @param Upload $upload The Upload model instance representing the file to delete
     * @return bool True if the file was successfully deleted, false otherwise
     */
    protected function deleteFromSftp(Upload $upload): bool
    {
        return Storage::disk('sftp')->delete($upload->path);
    }

    /**
     * Delete a file from Dropbox.
     *
     * @param Upload $upload The Upload model instance representing the file to delete
     * @return bool True if the file was successfully deleted, false otherwise
     */
    protected function deleteFromDropbox(Upload $upload): bool
    {
        return Storage::disk('dropbox')->delete($upload->path);
    }
}
