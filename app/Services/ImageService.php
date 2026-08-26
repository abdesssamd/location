<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ImageService
{
    public function store(UploadedFile $file, string $directory, int $maxWidth = 1200, int $quality = 80): string
    {
        $disk = config('filesystems.default');
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

        $name = Str::random(32).'.webp';
        $path = $directory.'/'.$name;
        $tmp = sys_get_temp_dir().'/'.$name;

        imagewebp($image, $tmp, $quality);
        imagedestroy($image);

        \Illuminate\Support\Facades\Storage::disk($disk)->put($path, file_get_contents($tmp));

        @unlink($tmp);

        return $path;
    }
}