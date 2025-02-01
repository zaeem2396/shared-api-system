<?php

namespace App\Utils;

use Exception;
use ImageKit\ImageKit as ImageKitBase;
use Intervention\Image\ImageManager;

class Intervention
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

    public function resizeImageAndUploadToImageKit($file)
    {
        try {
            $intervention = ImageManager::imagick();

            $sizes = [
                's' => 100,
                'm' => 300,
                'l' => 600
            ];
            $imageUrls = [];

            foreach ($sizes as $sizeKey => $width) {
                $resizedImg = $intervention->read($file->getPathname())->resize(width: $width);
                /* Convert image to base 64 */
                $base64File = 'data:image/' . $file->getClientOriginalExtension() . ';base64,' . base64_encode($resizedImg->encode());
                /* Upload image to imageKit */
                $uploadedFile = $this->imageKit->uploadFile([
                    'file' => $base64File,
                    'fileName' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . "_{$sizeKey}." . $file->getClientOriginalExtension(),
                    'folder' => 'vendora'
                ]);

                $imageUrls[$sizeKey] = $uploadedFile->result->url;
            }

            return [
                'status' => 200,
                'message' => 'Image uploaded successfully!',
                'images' => $imageUrls
            ];
        } catch (Exception $e) {
            return [
                'status' => 500,
                'message' => 'File upload failed',
                'moreinfo' => $e->getMessage()
            ];
        }
    }
}
