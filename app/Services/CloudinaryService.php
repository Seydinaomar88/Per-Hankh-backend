<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CloudinaryService
{
    public function upload(UploadedFile $file, string $folder = 'tasks')
    {
        try {
            $fileName = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs($folder, $fileName, 'public');
            
            return [
                'public_id' => $path,
                'secure_url' => asset('storage/' . $path),
                'bytes' => $file->getSize(),
                'format' => $file->getClientOriginalExtension(),
            ];
        } catch (\Exception $e) {
            Log::error('Upload error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(string $publicId)
    {
        try {
            if (Storage::disk('public')->exists($publicId)) {
                Storage::disk('public')->delete($publicId);
            }
            return true;
        } catch (\Exception $e) {
            Log::error('Delete error: ' . $e->getMessage());
            return false;
        }
    }
}