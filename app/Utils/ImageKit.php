<?php

namespace App\Utils;

use Exception;
use ImageKit\ImageKit as ImageKitBase;

class ImageKit
{
    public function uploadToImgKit($file)
    {
        try {
            $imageKit = new ImageKitBase(
                env('IMGKIT_PUBLIC_KEY'),
                env('IMGKIT_PRIVATE_KEY'),
                env('IMGKIT_URL')
            );

            $fileContent = file_get_contents($file->getRealPath());
            $base64File = 'data:' . $file->getMimeType() . ';base64,' . base64_encode($fileContent);

            $uploadedFile = $imageKit->uploadFile([
                'file' => $base64File,
                'fileName' => $file->getClientOriginalName(),
                'folder' => 'newzy'
            ]);
            return [
                'url' => $uploadedFile->result->url,
                'public_id' => $uploadedFile->result->fileId
            ];
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function deleteFromImgKit($publicId)
    {
        try {
            $imageKit = new ImageKitBase(
                env('IMGKIT_PUBLIC_KEY'),
                env('IMGKIT_PRIVATE_KEY'),
                env('IMGKIT_URL')
            );
            $isImgDeleted = $imageKit->deleteFile($publicId);
            if ($isImgDeleted->responseMetadata->statusCode == 204) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
