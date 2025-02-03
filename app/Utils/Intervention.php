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
            $intervention = ImageManager::gd();

            $sizes = [
                's' => ['width' => 100, 'height' => 75],
                'm' => ['width' => 300, 'height' => 225],
                'l' => ['width' => 600, 'height' => 450]
            ];
            $imageUrls = [];

            foreach ($sizes as $sizeKey => $dimensions) {
                $newWidth = $dimensions['width'];
                $newHeight = $dimensions['height'];

                // Read & resize image to specified width and height
                $resizedImage = $intervention->read($file->getPathname())->resize(width: $newWidth, height: $newHeight);

                // Convert resized image to Base64
                $base64File = 'data:image/' . $file->getClientOriginalExtension() . ';base64,' . base64_encode($resizedImage->encode());

                // Upload to ImageKit
                $uploadedFile = $this->imageKit->uploadFile([
                    'file' => $base64File,
                    'fileName' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . "_{$sizeKey}.",
                    'folder' => 'vendora'
                ]);

                // Store uploaded file URL with dimensions
                $imageUrls[$sizeKey] = [
                    'url' => $uploadedFile->result->url,
                    'width' => $newWidth,
                    'height' => $newHeight
                ];
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
