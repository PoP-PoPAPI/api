<?php
namespace PoP\API\DirectiveResolvers;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

trait URLRequestDirectiveResolverTrait
{
    protected function requestJSON(string $url, array $bodyJSONQuery = [], string $method = 'POST'): array
    {
        $client = new Client();
        try {
            $options = [
                RequestOptions::JSON => $bodyJSONQuery,
            ];
            // var_dump($method, $url, $options);die;
            $response = $client->request($method, $url, $options);
            if ($response->getStatusCode() != 200) {
                // Do nothing
                return [];
            }
            $contentType = 'application/json';
            if (substr($response->getHeaderLine('content-type'), 0, strlen($contentType)) != $contentType) {
                // Do nothing
                return [];
            }
            $body = $response->getBody();
            if (!$body) {
                // Do nothing
                return [];
            }
            return json_decode($body, JSON_FORCE_OBJECT);
        } catch (RequestException $exception) {
            return new Error(
                'request-failed',
                $exception->getMessage()
            );
        }
        return [];
    }
}
