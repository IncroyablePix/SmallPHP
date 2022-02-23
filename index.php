<?php
require_once __DIR__."/controllers/test_controller.php";
require_once __DIR__."/utils/cors.php";
require_once __DIR__."/utils/headers.php";
require_once __DIR__."/utils/jwt.php";

use SmallPHP\Controller\TestController;
use function SmallPHP\Cors\use_cors;
use function SmallPHP\CurrentJwt\extract_token_data;
use function SmallPHP\CurrentJwt\extract_from_jwt;
use function SmallPHP\Headers\get_request_headers;
use SmallPHP\CurrentJwt\InvalidTokenException;
use SmallPHP\Controller\HttpResponse;

use_cors();

//--- Request params

$method = $_SERVER["REQUEST_METHOD"];
$request = array_values(array_filter(explode("/", $_GET["path"] ?? ""), function($part) { return !empty($part); }));//explode("/", substr(@$_SERVER["REQUEST_URI"], 1));
$headers = get_request_headers();

//--- Authorization extraction

$token_val = null;

try
{
    $token = extract_from_jwt(array_key_exists("Authorization", $headers) ? $headers["Authorization"] : "");
    $token_val = extract_token_data($token);
}
catch(InvalidTokenException $e)
{
    $token_val = null;
}

//--- Routing

$controllers = [
    new TestController($request, $token_val, $method)
];

$controller = null;
$response = new HttpResponse();

if(count($request) > 0)
{
    foreach ($controllers as $c)
    {
        if ($c->get_name() == $request[0])
        {
            $controller = $c;
            break;
        }
    }
}

if($controller != null)
    $response = $controller->execute();


//--- Response
echo $response->body;
http_response_code($response->status_code);
foreach($response->headers as $h => $v)
    header($h . ": " . $v);
