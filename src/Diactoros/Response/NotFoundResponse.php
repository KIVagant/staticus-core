<?php
namespace Staticus\Diactoros\Response;

use Zend\Diactoros\Response\JsonResponse;

class NotFoundResponse extends JsonResponse
{
    /**
     * Create an empty response with the 404 status code
     *
     * @param mixed $data Explanation
     * @param array $headers Headers for the response, if any.
     */
    public function __construct($data, array $headers = [])
    {
        parent::__construct($data, 404, $headers);
    }
}
