<?php

class JWTCodec
{

    public function __construct(private string $key)
    {

    }

    public function encode(array $payload): string
    {
        $header = json_encode([
            "typ" => "JWT",
            "alg" => "HS256"
        ]);

        // base64url encoding is basically just URL-safe base64 encoding
        $header = $this->base64urlEncode($header);

        $payload = json_encode($payload);
        $payload = $this->base64urlEncode($payload);
        // we can generate the signature based on these two value. We'll use the hash_hmac function for this.
        // the secret key needs to be at least the same size as the hash output

        // JWT contains 3 part and combine with dot(.)
        // header.payload.signature
        $signature = hash_hmac(
            "sha256",
            "$header.$payload",
            $this->key,
            true
        );

        $signature = $this->base64urlEncode($signature);

        return "$header.$payload.$signature";
    }

    public function decode(string $token): array
    {

        if (!preg_match("/^(?<header>.+)\.(?<payload>.+)\.(?<signature>.+)$/", $token, $matches)) {
            throw new InvalidArgumentException("invalid token format");
        }


        $signature = hash_hmac(
            "sha256",
            "{$matches["header"]}.{$matches["payload"]}",
            $this->key,
            true
        );

        $signature_from_token = $this->base64urlDecode($matches["signature"]);
        // we have two signature that we can compare. As both of these are hashes, we can use hash_equals function, which adds a little bit of extra security than just using a comparison operator.

        if (!hash_equals($signature, $signature_from_token)) {

            throw new InvalidSignatureException();

        }

        $payload = json_decode($this->base64urlDecode($matches["payload"]), true);

        if ($payload["exp"] < time()) {

            throw new TokenExpiredException();

        }

        return $payload;

    }

    private function base64urlEncode(string $text): string
    {
        return str_replace(
            ["*", "/", "="],
            ["-", "_", ""],
            base64_encode($text)
        );
    }

    private function base64urlDecode(string $text): string
    {
        return base64_decode(
            str_replace(
                ["-", "_"],
                ["+", "/"],
                $text
            )
        );
    }

}