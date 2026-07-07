<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class FileUploadService
{
    public function moveTempFileToPublic($file, string $filePrefix = 'file', string $folder = 'upload')
    {
        if ($file instanceof \Illuminate\Http\UploadedFile) {
            $fileName = $filePrefix . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $destinationFolder = public_path($folder);

            if (!File::exists($destinationFolder)) {
                File::makeDirectory($destinationFolder, 0777, true);
            }

            $file->move($destinationFolder, $fileName);
            return 'public/' . $folder . '/' . $fileName;
        }

        if (is_string($file)) {
            $path = parse_url($file, PHP_URL_PATH);

            if (str_contains($file, '/temp/')) {
                if (str_contains($path, '/storage/')) {
                    $path = substr($path, strpos($path, '/storage/') + 9);
                }

                if (!str_starts_with($path, 'temp/')) {
                    return null;
                }

                $sourcePath = storage_path('app/public/' . $path);
                if (File::exists($sourcePath)) {
                    $fileName = $filePrefix . '_' . time() . '_' . uniqid() . '.' . pathinfo($path, PATHINFO_EXTENSION);
                    $destinationFolder = public_path($folder);

                    if (!File::exists($destinationFolder)) {
                        File::makeDirectory($destinationFolder, 0777, true);
                    }

                    $destinationPath = $destinationFolder . '/' . $fileName;
                    File::move($sourcePath, $destinationPath);

                    return 'public/' . $folder . '/' . $fileName;
                }
            }

            if (str_contains($file, '/upload/') || str_contains($file, 'upload/')) {
                $pos = strpos($path, 'upload/');
                if ($pos !== false) {
                    return 'public/' . substr($path, $pos);
                }
            }

            if (str_contains($file, '/uploade/') || str_contains($file, 'uploade/')) {
                $pos = strpos($path, 'uploade/');
                if ($pos !== false) {
                    return 'public/' . substr($path, $pos);
                }
            }

            if (str_contains($file, '/uploads/') || str_contains($file, 'uploads/')) {
                $pos = strpos($path, 'uploads/');
                if ($pos !== false) {
                    return 'public/' . substr($path, $pos);
                }
            }
        }

        return null;
    }
}
