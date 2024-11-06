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
            ])->getSecurePath();
            return $uploadedFileUrl;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
