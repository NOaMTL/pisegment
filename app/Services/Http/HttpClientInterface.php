<?php

declare(strict_types=1);

namespace App\Services\Http;

interface HttpClientInterface
{
    /**
     * Send a GET request
     *
     * @param  string  $url  Request URL
     * @param  array  $options  Additional options (headers, query params, etc.)
     * @return array Response data
     */
    public function get(string $url, array $options = []): array;

    /**
     * Send a POST request
     *
     * @param  string  $url  Request URL
     * @param  array  $data  Request body data
     * @param  array  $options  Additional options (headers, etc.)
     * @return array Response data
     */
    public function post(string $url, array $data = [], array $options = []): array;

    /**
     * Send a PUT request
     *
     * @param  string  $url  Request URL
     * @param  array  $data  Request body data
     * @param  array  $options  Additional options
     * @return array Response data
     */
    public function put(string $url, array $data = [], array $options = []): array;

    /**
     * Send a PATCH request
     *
     * @param  string  $url  Request URL
     * @param  array  $data  Request body data
     * @param  array  $options  Additional options
     * @return array Response data
     */
    public function patch(string $url, array $data = [], array $options = []): array;

    /**
     * Send a DELETE request
     *
     * @param  string  $url  Request URL
     * @param  array  $options  Additional options
     * @return array Response data
     */
    public function delete(string $url, array $options = []): array;

    /**
     * Set the authentication token
     *
     * @param  string  $token  Bearer token
     */
    public function withToken(string $token): self;

    /**
     * Set custom headers
     *
     * @param  array  $headers  Headers to add
     */
    public function withHeaders(array $headers): self;

    /**
     * Enable or disable proxy
     *
     * @param  string|null  $proxy  Proxy URL (e.g., 'http://proxy.example.com:8080')
     */
    public function withProxy(?string $proxy): self;

    /**
     * Set the token refresh callback
     *
     * This callback will be called when a 401 response is received
     * It should return a new access token
     *
     * @param  callable  $callback  Callback that returns a new token
     */
    public function onTokenExpired(callable $callback): self;

    /**
     * Set the base URI for all requests
     *
     * @param  string  $baseUri  Base URI
     */
    public function withBaseUri(string $baseUri): self;

    /**
     * Get the raw response from the last request
     *
     * @return mixed
     */
    public function getLastResponse();

    /**
     * Set the maximum number of retries
     *
     * @param  int  $retries  Maximum retries (default: 3)
     */
    public function setMaxRetries(int $retries): self;

    /**
     * Set custom token error indicators
     *
     * These strings will be searched in error responses to detect token issues
     *
     * @param  array  $indicators  Array of strings to detect token errors
     */
    public function setTokenErrorIndicators(array $indicators): self;

    /**
     * Set request timeouts
     *
     * @param  int  $timeout  Total timeout in seconds (default: 30)
     * @param  int  $connectTimeout  Connection timeout in seconds (default: 10)
     */
    public function setTimeouts(int $timeout, int $connectTimeout): self;

    /**
     * Set custom logger (optional)
     *
     * Allows logging without Laravel dependency.
     * Compatible with PSR-3 LoggerInterface or any callable.
     *
     * @param  callable  $logger  Function with signature: function(string $level, string $message, array $context)
     */
    public function setLogger(callable $logger): self;
}
