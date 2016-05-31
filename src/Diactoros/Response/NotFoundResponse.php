<?php
namespace Staticus\Diactoros\Response;

use Zend\Diactoros\Response\EmptyResponse;

class NotFoundResponse extends EmptyResponse
{
    /**
     * Create an empty response with the 404 status code
     *
     * @param array $headers Headers for the response, if any.
     */
    public function __construct(array $headers = [])
    {
        parent::__construct(404, $headers);
    }
}
