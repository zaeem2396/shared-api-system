<?php

namespace App\Utils;

use Exception;

class Cloudinary
{
    public function store($file)
    {
        try {
            $uploadedFileUrl = cloudinary()->upload($file->getRealPath(), [
                'folder' => 'newzy'
            ]);
            return [
                'url' => $uploadedFileUrl->getSecurePath(),
                'public_id' => $uploadedFileUrl->getPublicId(),
            ];
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function delete($publicId)
    {
        try {
            $isImgDeleted = cloudinary()->uploadApi()->destroy($publicId);
            if ($isImgDeleted['ok']) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
