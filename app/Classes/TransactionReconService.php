<?php


namespace App\Classes;


use App\Classes\Utils\JWTUtils;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class TransactionReconService
{
    private $baseUrl = "https://checkout.digipayzone.com";
    private $fetchStatusEndPoint = "/api/v1/fetch/transaction/bank/status";
    private $actionStatusEndPoint = "/api/v1/recon/bank/transaction";

    public function getTransactionFromBank($merchantId, $transactionId, $transactionToken) {
        try {
            $jwtToken = JWTUtils::createJWT($merchantId, $transactionId, $transactionToken);
            $requestHeader = [
                "Authorization" => $jwtToken
            ];
            $payload = [
                'transaction_id'    => $transactionId,
                'transaction_token' => $transactionToken,
            ];

            return $this->sendRequest(
                $this->fetchStatusEndPoint,
                $requestHeader,
                $payload
            );
        } catch (\Exception $ex) {
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return null;
        }
    }

    public function reconTransactionFromBank($merchantId, $transaction_id, $txn_token, $action)
    {
        try {
            $jwtToken = JWTUtils::createJWT($merchantId, $transaction_id, $txn_token);
            $requestHeader = [
                "Authorization" => $jwtToken
            ];
            $payload = [
                'transaction_id' => $transaction_id,
                'transaction_token' => $txn_token,
                'action' => $action,
            ];

            return $this->sendRequest(
                $this->actionStatusEndPoint,
                $requestHeader,
                $payload
            );
        } catch (\Exception $ex) {
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return null;
        }
    }

    private function sendRequest($end_point, $requestHeader, $requestBody) {
        try {
            $client = new Client([
                'base_uri' => $this->baseUrl
            ]);

            $client_response = $client->post($end_point, [
                "headers"   => $requestHeader,
                "json"      => $requestBody
            ]);
            return json_decode($client_response->getBody()->getContents());
        } catch (\InvalidArgumentException | ConnectException $ex) {
            Log::debug('Error in ConnectException | InvalidArgumentException', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);

            return null;
        } catch (GuzzleException $ex) {
            Log::debug('Error in GuzzleException', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return json_decode($ex->getResponse()->getBody()->getContents());
        }
    }
}
