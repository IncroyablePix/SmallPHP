<?php

namespace SmallPHP\Controller;

require_once __DIR__."/controller.php";

use SmallPHP\CurrentJwt\TokenData;
use SmallPHP\Controller\HttpResponse;

class TestController extends Controller
{
    public function __construct(array $endpoint, ?TokenData $token_data, string $method)
    {
        parent::__construct($endpoint, $token_data, $method);

        $this->add_endpoint("GET", "", [$this, "get_json"]);
        $this->add_endpoint("GET", "{string:value}", [$this, "get_query_param"]);
    }

    public function get_json(array $query_params, HttpResponse $response): void
    {
        $response->status_code = 200;
        $response->body = json_encode(["Hello", "World"]);
        $response->headers["Content-Type"] = "application/json";
    }

    public function get_query_param(array $query_param, HttpResponse $response): void
    {
        $response->status_code = 200;
        $response->body = "The value is ".$query_param["value"];
    }

    public function get_name(): string
    {
        return "test";
    }
}
