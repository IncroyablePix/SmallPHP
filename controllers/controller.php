<?php
namespace SmallPHP\Controller;

require_once(__DIR__."/../utils/jwt.php");

use SmallPHP\CurrentJwt\TokenData;

abstract class RouteElement
{
    public abstract function get_name(): string;
    public abstract function matches(string $element): bool;
}

class StaticRouteElement extends RouteElement
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function matches(string $element): bool
    {
        return $element == $this->name;
    }

    public function get_name(): string
    {
        return $this->name;
    }
}

class ParameterRouteElement extends RouteElement
{
    private string $type;
    private string $name;

    public function __construct(string $type, string $name)
    {
        $this->type = $type;
        $this->name = $name;
    }

    public function matches(string $element): bool
    {
        if(is_numeric($element) && $this->type == "int")
            return true;
        else if(is_string($element) && $this->type == "string")
            return true;

        return false;
    }

    public function get_name(): string
    {
        return $this->name;
    }
}

class Route
{
    const URL_PARAM_REGEX = "{(int|string):([a-zA-Z0-9_]+)}";

    private array $parts;
    private $callback;

    public function __construct(array $parts, callable $method)
    {
        $this->parts = [];
        $this->callback = $method;

        $i = 0;

        foreach($parts as $part)
        {
            $matches = [];

            if(preg_match(self::URL_PARAM_REGEX, $part, $matches))
            {
                $this->parts[$i] = new ParameterRouteElement($matches[1], $matches[2]);
            }
            else
            {
                $this->parts[$i] = new StaticRouteElement($part);
            }

            $i ++;
        }
    }

    public function try_route(array $parts, HttpResponse $response): bool
    {
        $query_params = [];
        for($i = 0; $i < count($parts); $i ++)
        {
            $route_element = $this->parts[$i];
            if(!$route_element->matches($parts[$i]))
                return false;
            else
            {
                $query_params[$route_element->get_name()] = $parts[$i];
            }
        }

        call_user_func($this->callback, $query_params, $response);
        return true;
    }
}

class HttpResponse
{
    public int $status_code = 404;
    public string $body = "";
    public array $headers = [];
}

abstract class Controller
{
    // protected \Doctrine\ORM\EntityManager $entity_manager;
    protected array $endpoint;
    protected ?TokenData $token_data;
    protected string $method;
    protected array $endpoints;

    public function __construct(array $endpoint, ?TokenData $token_data, string $method)
    {
        // $this->entity_manager = $entity_manager;
        $this->endpoint = $endpoint;
        $this->token_data = $token_data;
        $this->method = $method;
        $this->endpoints = [
            "POST" => [],
            "GET" => [],
            "PUT" => [],
            "DELETE" => []
        ];
    }

    public function add_endpoint(string $method, string $route, callable $callback): void
    {
        $route_parts = preg_split("#/#", $route);
        array_unshift($route_parts, $this->get_name());
        $this->endpoints[strtoupper($method)][] = new Route($route_parts, $callback);
    }

    public abstract function get_name(): string;

    public function execute(): HttpResponse
    {
        $response = new HttpResponse();
        $parse_endpoints = $this->endpoints[$this->method];

        foreach($parse_endpoints as $ep)
        {
            if($ep->try_route($this->endpoint, $response))
            {
                break;
            }
        }

        return $response;
    }

    protected final function token_id(): int
    {
        return $this->token_data === null ?
            -1 :
            $this->token_data->get_id();
    }
}
?>
