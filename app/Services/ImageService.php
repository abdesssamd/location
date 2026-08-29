<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ImageService
{
    public function store(UploadedFile $file, string $directory, int $maxWidth = 1200, int $quality = 80): string
    {
        $disk = 'public';
        $image = imagecreatefromstring($file->getContent());

        if (! $image) {
            return $file->store($directory, $disk);
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > $maxWidth) {
            $ratio = $maxWidth / $width;
            $newWidth = $maxWidth;
            $newHeight = (int) round($height * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);

            if ($file->getClientOriginalExtension() === 'png' || str_contains($file->getMimeType(), 'png')) {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefill($resized, 0, 0, $transparent);
            }

            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        $webpSupported = function_exists('imagewebp')
            && defined('IMG_WEBP')
            && (imagetypes() & IMG_WEBP);

        $ext = $webpSupported ? 'webp' : 'jpg';
        $name = Str::random(32).'.'.$ext;
        $path = $directory.'/'.$name;
        $tmp = sys_get_temp_dir().DIRECTORY_SEPARATOR.$name;

        if ($webpSupported) {
            $ok = imagewebp($image, $tmp, $quality);
        } else {
            $bg = imagecreatetruecolor(imagesx($image), imagesy($image));
            imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
            imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
            imagedestroy($image);
            $image = $bg;
            $ok = imagejpeg($image, $tmp, $quality);
        }

        imagedestroy($image);

        if (empty($ok) || ! file_exists($tmp)) {
            return $file->store($directory, $disk);
        }

        \Illuminate\Support\Facades\Storage::disk($disk)->put($path, file_get_contents($tmp));

        @unlink($tmp);

        return $path;
    }
}