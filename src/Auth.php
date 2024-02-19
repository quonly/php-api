<?php

class Auth
{
    private int $user_id;
    public function __construct(
        private Usergateway $user_gateway,
        private JWTCodec $codec
    ) {
    }

    public function authenticateAPIKey(): bool
    {
        if (empty($_SERVER["HTTP_X_API_KEY"])) {

            http_response_code(400);
            echo json_encode([
                "message" => "missing API key"
            ]);
            return false;
        }

        // it's common to use the request headers to pass authentication details. it's common to use a header with X-API-Key. Request headers are available up in $_SERVER superglobal.

        $api_key = $_SERVER["HTTP_X_API_KEY"];

        $user = $this->user_gateway->getByAPIKey($api_key);

        if ($user === false) {
            // 401 unauthorized
            http_response_code(401);
            echo json_encode(["message" => "invalid API key"]);
            return false;
        }

        $this->user_id = $user["id"];

        return true;
    }

    public function getUserID(): int
    {
        return $this->user_id;
    }

    public function authenticateAccessToken(): bool
    {

        if (!preg_match("/^Bearer\s+(?<token>.*)$/", $_SERVER["HTTP_AUTHORIZATION"], $matches)) {
            http_response_code(400);
            echo json_encode(["message" => "incomplete authorization header"]);
            return false;
        }

        [, $token] = $matches;

        try {
            $data = $this->codec->decode($token);
        } catch (InvalidSignatureException) {
            http_response_code(401);
            echo json_encode(["message" => "invalid signature"]);
            return false;
        } catch (TokenExpiredException) {
            http_response_code(401);
            echo json_encode(["message" => "token has expired"]);
            return false;
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["message" => $e->getMessage()]);
            return false;
        }

        $this->user_id = $data["sub"];

        return true;
    }

}