<?php

namespace App\Utilities;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

class InternalResponse
{

    /*
     * This is mainly used for any responses between adapters / controllers and anywhere else applicable
     */

    protected $statusCode = 200;
    protected $message = '';
    protected $error = false;
    protected $errorCode = 0;


    /**
     * Function to return an error response.
     *
     * @param $message
     * @return mixed
     */
    public function respondWithError($message)
    {
        $this->error = true;
        $this->message = $message;
        return $this->respond();
    }

    /**
     * Function to return an unauthorized response.
     *
     * @param string $message
     * @return mixed
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
     * @return mixed
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
     * @return mixed
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
     * @return mixed
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
     * @return mixed
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
     * @return mixed
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
     * @return mixed
     */
    public function respondServiceUnavailable($message = "Service Unavailable!")
    {
        $this->statusCode = Response::HTTP_SERVICE_UNAVAILABLE;
        return $this->respondWithError($message);
    }

    /**
     * Throws a bad request exception with the validator's error messages.
     *
     * @param Validator $validator The validator to get the message from
     *
     * @return mixed
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
     * @param $data array The data to be included
     *
     * @return mixed
     *
     */
    public function respondCreated($data)
    {
        $this->statusCode = Response::HTTP_CREATED;
        return $this->respond($data);
    }

    /**
     * Function to return a generic response.
     *
     * @param $data array to be used in response.

     * @return array Return the response.
     */
    public function respond($data = null)
    {
        $meta = [
            'meta' => [
                'error' => $this->error ? true : false,
                'message' => $this->message,
                'status_code' => $this->statusCode,
            ]
        ];

        if (empty($data) && !is_array($data)) {
            $data = array_merge($meta, ['response' => null]);
        } else {
            $data = array_merge($meta, ['response' => $data]);
        }

        return $data;
    }

}
