<?php

use App\Utilities\FileStorageHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

const DEFAULT_MAX_LIMIT = 200;

/**
 * Adds additional data to sentry's logging prior to exception throwing
 *
 * @param $name
 * @param $data
 */
function set_log_extra($name, $data) {
    \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($name, $data) {
        $scope->setExtra($name, $data);
    });
    if (empty(config('sentry.dsn'))) {
        ini_set("xdebug.overload_var_dump", "off");
        if (is_subclass_of($data,Model::class)) {
            $data = $data->toArray();
        }
        ob_start();
        var_dump($data);
        $result = ob_get_clean();
        Log::error('[' . $name . '] ' . $result);
    }
}

/**
 * Dynamically retrieves data from a multi dimensional array
 *
 * @param $data
 * @param $toCall
 *
 * @return mixed
 */
function array_retrieve($data, $toCall)
{
    if (empty($data)) {
        return null;
    }
    $current = $data;
    foreach($toCall as $key) {
        if (!isset($current[$key])) {
            return null;
        }
        $current = $current[$key];
    }
    return $current;
}

function negate_array_values($array)
{
    $newArr = [];
    foreach ($array as $key => $value) {
        if (!is_array($value)) {
            $newArr[$key] = -$value;
        } else {
            $arr = [];
            foreach ($value as $item) {
                $arr[] = -$item;
            }
            $newArr[$key] = $arr;
        }
    }
    return $newArr;
}

function merge_and_sum_array($first, $second) {
    if (empty($first)) {
        return $second;
    }
    if (empty($second)) {
        return $first;
    }
    $newArr = [];
    foreach (array_keys($first + $second) as $key) {
        /*
         * Issues
         * need bracket both to sum the numbers, and + doesn't merge the array
         */
        $firstValue = isset($first[$key]) ? $first[$key] : (isset($second[$key]) && is_array($second[$key]) ? [] : 0);
        $secondValue = (isset($second[$key]) ? $second[$key] : (isset($first[$key]) && is_array($first[$key]) ? [] : 0));
        if (is_array($firstValue)) {
            $newArr[$key] = array_merge($firstValue, $secondValue);
        } else {
            $newArr[$key] = $firstValue + $secondValue;
        }
    }
    return $newArr;
}

/**
 * Returns a paginated collection
 *
 * @param $items
 * @param $perPage
 * @param null $page
 * @param array $options
 *
 * @return LengthAwarePaginator
 */
function paginate($items, $perPage = 10, $page = null, $options = [])
{
    $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
    $items = $items instanceof Collection ? $items : Collection::make($items);
    $paginator = new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    $paginator->setPath('');
    return $paginator;
}

/**
 * Returns a human readable datetime
 *
 * @param $datetime
 *
 * @return string
 */
function date_time_text($datetime)
{
    if (empty($datetime)) {
        return 'N/A';
    }
    if (is_string($datetime)) {
        $datetime = Carbon::parse($datetime);
    }
    return $datetime->format('g:i a, jS M Y');
}

/**
 * Upload base64 image to temp folder, and get url
 *
 * @param base64 $image
 * @param $shop
 * @return string
 */
function uploadImageFile($image, $shop)
{
    $actualImage = Image::make($image);
    $shopPath = "temp/shops/" . $shop->id . '/';
    $mimeParts = explode('/', $actualImage->mime());
    $filename = $shopPath . str_random(10) . '.' . $mimeParts[1];
    $imageStream = $actualImage->stream();
    Storage::put($filename, $imageStream->__toString(), ['visibility' => 'public']);

    return Storage::url($filename);
}

/**
 * convert camel case to pascal case
 *
 * @param $text
 * @return string
 */
function toPascalCase($text)
{
    return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $text));
}

/**
 * return true if array is associative, false if sequential
 *
 * @param array $array
 * @return bool
 */
function is_associative(array $array)
{
    if (array() === $array) return false;
    return array_keys($array) !== range(0, count($array) - 1);
}

/**
 * return true if array is empty
 *
 * @param array $array
 * @return bool
 */
function is_array_empty(array $array)
{
    return count(array_filter($array)) == 0;
}

function stringIsJson($data)
{
    return is_string($data) && is_array(json_decode($data, true));
}

function coverstringJsonToArray($data): array
{

    if (stringIsJson($data)) {
        return json_decode($data, true);
    }
    return [];
}

function saveColorThumbnail($value): array
{
    $path = 'lazada/products/variant_attributes/color-thumbnails/';

    if (stringIsJson($value)) {
        $value = json_decode($value, true);
    }
    if (is_array($value)) {
        foreach ($value as $key => $itemValue) {
            if (empty($itemValue['image_url']) && !empty($itemValue['data_url'])) {
                // save S3 => save path
                $value[$key]['image_url'] = FileStorageHelper::uploadImageByBase64($itemValue['data_url'], $path);
            }
            if (empty($value[$key]['image_url'])) {
                unset($value[$key]);
            }
        }
    }
    return $value;
}

/**
 * check is_sale_prop
 * return true or false
 *
 * @param array $array
 * @return bool
 */
function isSaleProp(?array $additional_data) : bool
{
    if (!$additional_data || !is_array($additional_data)) {
        return false;
    }
    if (!empty($additional_data) && !empty($additional_data['is_sale_prop'])) {
        if ($additional_data['is_sale_prop'] == 1) {
            return true;
        } else {
            return false;
        }
    }

    return false;
}