<?php
namespace SmallPHP\Cors;

require_once "config/cors-config.php";

function use_cors(): void
{
    if(isset($_SERVER["HTTP_ORIGIN"]))
    {
        foreach(DOMAINS as $domain)
        {
        if($_SERVER["HTTP_ORIGIN"] == $domain)
            {
                header("Access-Control-Allow-Origin: ".$domain);
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Max-Age: 86400');
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        {
            header("Access-Control-Allow-Methods: *");
        }

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        {
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
        }

        exit(0);
    }

    header("Access-Control-Allow-Methods: *");
    header("Access-Control-Allow-Headers: Authorization, Content-Type");
}
