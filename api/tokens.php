<?php

$payload = [
    "sub" => $user["id"],
    "name" => $user["name"],
    "exp" => time() + 20,
];


$access_token = $codec->encode($payload);

$refresh_token_expiry = time() + (60 * 60 * 24 * 5);

$refresh_token = $codec->encode([
    "sub" => $user["id"],
    "exp" => $refresh_token_expiry
]);

// For the id of the user we use the "sub" or subject claim.
$payload = json_encode([
    "access_token" => $access_token,
    "refresh_token" => $refresh_token
]);

return [$refresh_token, $payload];