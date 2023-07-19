<?php


namespace App\Classes\Utils;


use Carbon\Carbon;

class JWTUtils
{

    private const PRIVATE_KEY  = "EOy-xNhDZmrdoLOJnkwJXrkrzb7i9Bu8VofNygzoXOM=";

    public static function createJWT($merchantId, $transactionId, $transactionToken) {

        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256',
        ];

        $payload = [
            'iss'           => 'DIGIPAYZONE',
            'timestamp'     => Carbon::now()->unix(),
            'merchantId'    => $merchantId,
        ];

        $requestData =
            [
                'transaction_id'    => $transactionId,
                'txn_token'         => $transactionToken,
            ];

        // Encode Header to Base64Url String
        $base64UrlData  =  self::base64UrlEncode(json_encode($requestData));

        // Encode Header to Base64Url String
        $base64UrlHeader =  self::base64UrlEncode(json_encode($header));
        // Encode Payload to Base64Url String
        $base64UrlPayload =  self::base64UrlEncode(json_encode($payload));
        // Create Signature Hash
        $signature = hash_hmac('sha256', $base64UrlHeader . "." .$base64UrlData . "." . $base64UrlPayload, self::PRIVATE_KEY, true);
        // Encode Signature to Base64Url String
        $base64UrlSignature = self::base64UrlEncode($signature);

        // Create JWT
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

    }

    private static function base64UrlEncode($data)
    {
        return str_replace(
            ['+', '/', '='],
            ['-', '_', ''],
            base64_encode($data)
        );
    }
}
