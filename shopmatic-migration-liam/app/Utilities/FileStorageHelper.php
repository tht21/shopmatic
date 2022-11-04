<?php

namespace App\Utilities;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\FileNotFoundException;

class FileStorageHelper {

    /**
     * Stores the file based on the environment and tenant
     *
     * @param string $path The path of the file
     * @param UploadedFile $file
     *
     * @param bool $cloud
     *
     * @return false|string The path if stored
     */
    public static function storeFile($path, UploadedFile $file, $cloud=false) {
        $actualPath = '';
        if (config('app.env') !== 'production') {
            $actualPath .= config('app.env') . '-env/';
        }
        $actualPath .= $path;

        if ($cloud) {
            return $file->store($actualPath, 's3');
        } else {
            return $file->store($actualPath);
        }
    }

    /**
     * Stores the file based on the environment and tenant
     *
     * @param $document
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public static function viewFile($document) {

        try {

            /** @var AwsS3Adapter $adapter */
            $adapter = Storage::disk('s3')->getDriver();

            $stream = $adapter->readStream($document->file_url);

            $headers = [
                'Cache-Control' => 'must-revalidate',
                'Content-Type'  => $document->file_type,
                'Content-Disposition' => 'inline; filename="'.stripslashes($document->title) . '.' . pathinfo($document->file_url, PATHINFO_EXTENSION) . '"'
            ];

            return Response::stream(function() use($stream) {
                while (!feof($stream)) {
                    echo fread($stream, 1024);
                }
                fclose($stream);
            }, 200, $headers);
        } catch (FileNotFoundException $e) {
            Log::error($e);
            flash()->error('Unfortunately, we are unable to retrieve that file. Please contact customer support if the issue persists.');
            return back();
        }
    }

    /**
     * Downloads the file
     *
     * @param $document
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     *
     */
    public static function downloadFile($document) {
        try {

            /** @var AwsS3Adapter $adapter */
            $adapter = Storage::disk('s3')->getDriver();

            $stream = $adapter->readStream($document->file_url);

            $headers = [
                'Cache-Control'       => 'must-revalidate',
                'Content-Type'        => $document->file_type,
                'Content-Disposition' => 'attachment; filename="' . stripslashes($document->title) . '.' . pathinfo($document->file_url, PATHINFO_EXTENSION) . '"'
            ];

            return Response::stream(function () use ($stream) {
                while (!feof($stream)) {
                    echo fread($stream, 1024);
                }
                fclose($stream);
            }, 200, $headers);
        } catch (FileNotFoundException $e) {
            Log::error($e);
            flash()->error('Unfortunately, we are unable to locate that file. Please contact customer support if the issue persists.');
            return back();
        }
    }

    /**
     * Downloads the file
     *
     * @param $document
     *
     * @return bool
     *
     */
    public static function deleteFile($document) {
        try {

            /** @var AwsS3Adapter $adapter */
            $adapter = Storage::disk('s3')->getDriver();

            return $adapter->delete($document->file_url);

        } catch (FileNotFoundException $e) {
            Log::error($e);
        }
        return false;
    }

    public static function uploadImageByBase64(string $base64Image, string $path = 'images/', string $imageName = null, string $visibility = 'public') {
        // Decode base64 to image
        @list($type, $fileData) = explode(';', $base64Image);
        @list(, $extension) = explode('/', $type);
        @list(, $fileData) = explode(',', $fileData);
        $decodedImage = base64_decode($fileData);
        // Make image path for variant attrbutes image
        if (!$imageName) {
            $imageName = time() . str_random(10) . "-image.".$extension;
        }
        if (empty($decodedImage) || empty($extension)) {
            return null;
        }
        $actualPath = $path . $imageName;
        if (config('app.env') !== 'production') {
            $actualPath = config('app.env') . '-env/'. $actualPath;
        }
        // Upload image to S3 then get link
        Storage::disk('s3')->put($actualPath, $decodedImage, ['visibility' => $visibility]);

        return Storage::disk('s3')->url($actualPath);
    }

}
