<?php
namespace SmallPHP\CurrentJwt;

require_once "config/jwt-config.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use UnexpectedValueException;
use Firebase\JWT\ExpiredException;

use Exception;
use Throwable;

class TokenData
{
    private int $id;
    private bool $is_admin;

    public function __construct(int $id, bool $is_admin)
    {
        $this->id = $id;
        $this->is_admin = $is_admin;
    }

    public function get_id(): int
    {
        return $this->id;
    }

    public function is_admin(): bool
    {
        return $this->is_admin;
    }
}

function extract_from_jwt(?string $authorization_header): string
{
    if($authorization_header == null)
    {
        throw new InvalidTokenException("No authorization header");
    }
    $splits = explode(" ", $authorization_header);
    $token = "";

    if(sizeof($splits) === 2)
    {
        $token = $splits[1];
    }

    return $token;
}

function create_token(TokenData $data, int $duration_in_minutes): string
{
    $now = time();

    $token_payload = array(
        "iss" => ISSUER,                                // Issuer
        "aud" => AUDIENCE,                              // Audience
        "nbf" => $now,                                      // Not before
        "iat" => $now,                                      // Issued at
        "exp" => ($now + 60 * $duration_in_minutes),        // Expiration timestamp
        "sub" => json_encode([ "id" => $data->get_id(), "is_admin" => $data->is_admin()])
    );

    return JWT::encode($token_payload, PRIVATE_KEY, "HS256");
}

function extract_token_data(string $token): TokenData
{
    try
    {
        $decoded = JWT::decode($token, new Key(PRIVATE_KEY, "HS256"));
    }
    catch(UnexpectedValueException $e)
    {
        throw new InvalidTokenException("Wrong number of segments", 0, $e);
    }
    catch(SignatureInvalidException $e)
    {
        throw new InvalidTokenException("Invalid token", 0, $e);
    }
    catch(ExpiredException $e)
    {
        throw new InvalidTokenException("Expired token", 0, $e);
    }

    if($decoded->iss !== ISSUER ||
        $decoded->aud !== AUDIENCE)
        {
            throw new InvalidTokenException("Invalid audience or issuer");
        }

    if($decoded->nbf > time() ||
        $decoded->exp < time())
    {
        throw new InvalidTokenException("Invalid timelapse");
    }

    //---

    $json_data = json_decode($decoded->sub);
    $token_data = new TokenData($json_data->id, $json_data->is_admin);

    return $token_data;
}

//---

class InvalidTokenException extends Exception
{
    public function __construct(string $message, $code = 0, $previous = null)
    {
        if($previous instanceof Throwable)
            parent::__construct($message, is_numeric($code) ? $code : 0, $previous);
        else
            parent::__construct($message, is_numeric($code) ? $code : 0, null);
    }

    public function __toString() : string
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
