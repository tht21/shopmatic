<?php

namespace App\Utilities;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\FileNotFoundException;

class ImageHelper {

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
    public static function validateImageUrl($imageUrl) {
        $return = ['isValid' => true, 'message' => 'Ok', 'contentImage' => null];
        if (!empty($imageUrl)) {
            $client = new Client();
            try {
                $response = $client->request('GET', $imageUrl, ['timeout' => 20]);
                if ($response->getStatusCode() == 200) {
                    // Validate if the url is actually an image url by validating the header
                    $imageHeader = array_change_key_case(get_headers($imageUrl, 1));
                    if (!isset($imageHeader["content-length"]) || $imageHeader["content-length"] == 0) {
                        $return['isValid'] = false;
                        $return['message'] = 'The file size is unknown, please change to another image';
                    }
                    if (str_contains($imageHeader["content-type"], 'image')) {
                        $return['contentImage'] = $response->getBody()->getContents();
                    }
                } else {
                    $statusCode = $response->getStatusCode();
                    $return['isValid'] = false;
                    $return['message'] = sprintf("Not able to fetch file header information.Status code [%s] returned, please change to another image",$statusCode);
                }
            } catch (ConnectException $e) {
                // Connection exceptions are not caught by RequestException
                $return['isValid'] = false;
                $return['message'] = "Networking error(connection timeout, DNS errors, etc).Failed to get header information";

            } catch (RequestException $e) {
                $return['isValid'] = false;
                $return['message'] = "Networking error(connection timeout, DNS errors, etc).Failed to get header information";
            }
        }
        return $return;
    }
}
