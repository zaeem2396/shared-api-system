<?php

namespace App\Utils;

use Exception;
use ImageKit\ImageKit as ImageKitBase;

class ImageKit
{
    private $imageKit;

    public function __construct()
    {
        $this->imageKit = new ImageKitBase(
            env('IMGKIT_PUBLIC_KEY'),
            env('IMGKIT_PRIVATE_KEY'),
            env('IMGKIT_URL')
        );
    }

    public function uploadToImgKit($file)
    {
        try {
            $base64File = $this->getBase64File($file);

            $uploadedFile = $this->imageKit->uploadFile([
                'file' => $base64File,
                'fileName' => $file->getClientOriginalName(),
                'folder' => 'vendora'
            ]);

            return [
                'url' => $uploadedFile->result->url,
                'public_id' => $uploadedFile->result->fileId
            ];
        } catch (Exception $e) {
            // Log error and return a generic message
            return 'File upload failed: ' . $e->getMessage();
        }
    }

    public function deleteFromImgKit($publicId)
    {
        try {
            $isImgDeleted = $this->imageKit->deleteFile($publicId);
            return $isImgDeleted->responseMetadata->statusCode === 204;
        } catch (Exception $e) {
            // Log error and return a generic message
            return 'File deletion failed: ' . $e->getMessage();
        }
    }

    private function getBase64File($file)
    {
        $fileContent = file_get_contents($file->getRealPath());
        return 'data:' . $file->getMimeType() . ';base64,' . base64_encode($fileContent);
    }
}