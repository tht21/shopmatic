<?php

namespace App\Http\Controllers\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

	protected $statusCode = 200;
	protected $message = '';
	protected $error = false;
	protected $debugInfo = [];
	protected $errorCode = 0;

    /**
     * Function to return an error response.
     *
     * @param $message
     * @param array $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
	public function respondWithError($message, $data = [])
	{
		$this->error = true;
		$this->message = $message;
		return $this->respond($data);
	}

    /**
     * Function to return an unauthorized response.
     *
     * @param string $message
     *
     * @return \Illuminate\Http\JsonResponse
     */
	public function respondUnauthorizedError($message = 'Unauthorized!')
	{
		$this->statusCode = Response::HTTP_UNAUTHORIZED;
		return $this->respondWithError($message);
	}


    /**
     * Function to return a bad request response.
     *
     * @param string $message
     *
     * @return \Illuminate\Http\JsonResponse
     */
	public function respondBadRequestError($message = 'Bad Request!!')
	{
		$this->statusCode = Response::HTTP_BAD_REQUEST;
		return $this->respondWithError($message);
	}

    /**
     * Function to return forbidden error response.
     *
     * @param string $message
     *
     * @return \Illuminate\Http\JsonResponse
     */
	public function respondForbiddenError($message = 'Forbidden!')
	{
		$this->statusCode = Response::HTTP_FORBIDDEN;
		return $this->respondWithError($message);
	}

    /**
     * Function to return a Not Found response.
     *
     * @param string $message
     *
     * @return \Illuminate\Http\JsonResponse
     */
	public function respondNotFound($message = 'Resource Not Found')
	{
		$this->statusCode = Response::HTTP_NOT_FOUND;
		return $this->respondWithError($message);
	}

    /**
     * Function to return an internal error response.
     *
     * @param string $message
     *
     * @return \Illuminate\Http\JsonResponse
     */
	public function respondInternalError($message = 'Internal Server Error!')
	{
		$this->statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
		return $this->respondWithError($message);
	}

    /**
     * Function to return an internal error response.
     *
     * @param string $message
     *
     * @return \Illuminate\Http\JsonResponse
     */
	public function respondMethodNotAllowed($message = 'Method not allowed!')
	{
		$this->statusCode = Response::HTTP_METHOD_NOT_ALLOWED;
		return $this->respondWithError($message);
	}

    /**
     * Function to return a service unavailable response.
     *
     * @param string $message
     *
     * @return \Illuminate\Http\JsonResponse
     */
	public function respondServiceUnavailable($message = "Service Unavailable!")
	{
		$this->statusCode = Response::HTTP_SERVICE_UNAVAILABLE;
		return $this->respondWithError($message);
	}

    /**
     * Throws a bad request exception with the validator's error messages.
     *
     * @param Validator $validator The validator to get the message from.
     *
     * @return \Illuminate\Http\JsonResponse
     */
	public function showValidationError($validator)
	{
		$this->error = true;
		$this->statusCode = Response::HTTP_BAD_REQUEST;
		$this->message = implode(" ", $validator->errors()->all());
		return $this->respond();
	}

    /**
     * Function to return a created response
     *
     * @param array $data The data to be included
     *
     * @return \Illuminate\Http\JsonResponse
     */
	public function respondCreated($data)
	{
		$this->statusCode = Response::HTTP_CREATED;
		return $this->respond($data);
	}

    /**
     * Function to return a response with a message
     *
     * @param array $data The data to be included
     *
     * @param string $message The message to be shown in the meta of the response
     *
     * @return \Illuminate\Http\JsonResponse
     */
	public function respondWithMessage($data, $message)
	{
		$this->statusCode = Response::HTTP_OK;
		$this->message = $message;
		return $this->respond($data);
	}

	/**
	 * Adds debugging information to the response
	 *
	 * @param $data
	 */
	public function addDebugInfo($data)
	{
		if (config('app.debug')) {
			$this->debugInfo[] = $data;
		}
	}

	/**
	 * Function to return a generic response.
	 *
	 * @param array $data to be used in response.
	 * @param array $headers Headers to be used in response.
	 * @return \Illuminate\Http\JsonResponse Return the response.
	 */
	public function respond($data = [], $headers = [])
	{
		if (isset($data['meta'])) {
            $meta = [
                'meta' => [
                    'error' => $data['meta']['error'] ?? $this->error,
                    'message' =>$data['meta']['message'] ?? $this->message,
                    'status_code' => $data['meta']['status_code'] ?? $this->statusCode,
                ]
            ];
            unset($data['meta']);
        } else {
            $meta = [
                'meta' => [
                    'error' => $this->error,
                    'message' => $this->message,
                    'status_code' => $this->statusCode,
                ]
            ];
        }

	    if (isset($data['response']) && count($data) === 1) {
	        $data = $data['response'];
        }

		if (empty($data) && !is_array($data)) {
            $data = array_merge($meta, ['response' => null]);
        } else {
            $data = array_merge($meta, ['response' => $data]);
        }

		if (!empty($this->debugInfo)) {
			$data = array_merge($data, ['debug' => $this->debugInfo]);
		}

		return response()->json($data, $this->statusCode, $headers);
	}

    /**
     * Responds a paginated
     *
     * @param Request $request
     * @param array|\Illuminate\Contracts\Pagination\LengthAwarePaginator $items
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondPagination($request, $items)
    {
        if (!($items instanceof LengthAwarePaginator)) {
            $pagination = $this->paginate($request, $items);
        } else {
            $pagination = $items;
        }
        return $this->respond(['pagination' => $this->getPagination($pagination), 'items' => $pagination->items()]);
    }

	/**
	 * Returns a LengthAwarePaginator for an array collection
	 *
	 * @param Request $request
	 * @param array $items
	 * @return LengthAwarePaginator
	 */
	public function paginate(Request $request, $items)
    {
		$limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT);
		$page = intval($request->get('page', 1));
		$offset = ($page - 1) * $limit;
		$items = new LengthAwarePaginator(array_slice($items, $offset, $limit), count($items), $limit, $page);
		return $items;
	}

	/**
	 * Retrieves the pagination meta in an array format
	 *
	 * @param LengthAwarePaginator $item
	 * @return array
	 */
	public function getPagination(LengthAwarePaginator $item)
    {
		return [
			'total' => $item->total(),
			'current_page' => $item->currentPage(),
			'last_page' => $item->lastPage(),
			'from' => $item->firstItem(),
			'to' => $item->lastItem(),

		];
	}

    /**
     * Return a vue-table 2 formatted data
     *
     * @param $request
     * @param $items
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|Response
     */

	public function respondVueTable($request, $items)
    {
        if (!($items instanceof LengthAwarePaginator)) {
            $pagination = $this->paginate($request, $items);
        } else {
            $pagination = $items;
        }

        $data = $this->respond(['links' => ['pagination' => $pagination], 'data' => $pagination->items()]);

        $meta = $data->getData()->meta;
        $links = $data->getData()->response->links;
        $data = $data->getData()->response->data;

        $transform = json_encode(['meta' => $meta, 'links' => $links, 'data' => $data]);

        return response($transform);
    }
}
