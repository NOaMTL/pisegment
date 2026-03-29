<?php

declare(strict_types=1);

namespace App\Services\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class GuzzleHttpClient implements HttpClientInterface
{
    private Client $client;

    private array $defaultHeaders = [];

    private ?string $token = null;

    private ?string $proxy = null;

    private ?string $baseUri = null;

    private $lastResponse = null;

    private $tokenRefreshCallback = null;

    private int $maxRetries = 3;

    private array $tokenErrorIndicators = [
        'token_expired',
        'token_invalid',
        'invalid_token',
        'expired_token',
        'authentication_failed',
        'unauthorized',
    ];

    private int $timeout = 30;

    private int $connectTimeout = 10;

    /**
     * Optional logger callable: function(string $level, string $message, array $context)
     * Compatible with PSR-3 or custom loggers
     */
    private $logger = null;

    public function __construct()
    {
        $this->initializeClient();
    }

    /**
     * Initialize the Guzzle client with middleware
     */
    private function initializeClient(): void
    {
        $stack = HandlerStack::create();

        // Add retry middleware for token refresh on 401
        $stack->push(Middleware::retry(
            $this->retryDecider(),
            $this->retryDelay()
        ));

        // Add request middleware to inject token
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            if ($this->token) {
                return $request->withHeader('Authorization', 'Bearer '.$this->token);
            }

            return $request;
        }));

        $config = [
            'handler' => $stack,
            'http_errors' => false, // We'll handle errors manually
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
            'allow_redirects' => true,
            'verify' => true, // SSL verification
        ];

        if ($this->baseUri) {
            $config['base_uri'] = $this->baseUri;
        }

        if ($this->proxy) {
            $config['proxy'] = $this->proxy;
        }

        $this->client = new Client($config);
    }

    /**
     * Decide whether to retry a request
     */
    private function retryDecider(): callable
    {
        return function (
            int $retries,
            RequestInterface $request,
            ?ResponseInterface $response = null,
            ?\Exception $exception = null
        ) {
            // Don't retry if we've exceeded max retries
            if ($retries >= $this->maxRetries) {
                $this->log('warning', 'Max retries reached', [
                    'retries' => $retries,
                    'url' => (string) $request->getUri(),
                ]);

                return false;
            }

            // Check for token errors if we have a response
            if ($response && $this->tokenRefreshCallback) {
                $shouldRefreshToken = false;

                // Check HTTP status code 401
                if ($response->getStatusCode() === 401) {
                    $shouldRefreshToken = true;
                    $this->log('info', '401 Unauthorized detected, attempting token refresh', [
                        'retry' => $retries + 1,
                    ]);
                }

                // Check for token errors in response body
                if (! $shouldRefreshToken && in_array($response->getStatusCode(), [200, 400, 403])) {
                    $body = (string) $response->getBody();
                    $data = json_decode($body, true);

                    if ($data && $this->hasTokenError($data)) {
                        $shouldRefreshToken = true;
                        $this->log('info', 'Token error detected in response body, attempting token refresh', [
                            'retry' => $retries + 1,
                            'error_data' => $data,
                        ]);
                    }

                    // Reset body stream for next read
                    $response->getBody()->rewind();
                }

                // Attempt token refresh
                if ($shouldRefreshToken) {
                    try {
                        $newToken = call_user_func($this->tokenRefreshCallback);
                        if ($newToken) {
                            $this->token = $newToken;
                            $this->initializeClient();
                            $this->log('info', 'Token refreshed successfully, retrying request');

                            return true;
                        }
                    } catch (\Exception $e) {
                        $this->log('error', 'Token refresh failed', [
                            'error' => $e->getMessage(),
                            'retry' => $retries + 1,
                        ]);
                    }
                }
            }

            // Retry on network errors (timeout, connection issues, etc.)
            if ($exception) {
                $shouldRetry = $this->isRetryableException($exception);

                if ($shouldRetry) {
                    $this->log('warning', 'Network error detected, retrying request', [
                        'retry' => $retries + 1,
                        'exception' => get_class($exception),
                        'message' => $exception->getMessage(),
                    ]);
                }

                return $shouldRetry;
            }

            return false;
        };
    }

    /**
     * Check if response contains token error indicators
     */
    private function hasTokenError(array $data): bool
    {
        // Check in common error fields
        $errorFields = ['error', 'error_code', 'code', 'error_type', 'type', 'message', 'error_message'];

        foreach ($errorFields as $field) {
            if (isset($data[$field])) {
                $value = is_string($data[$field]) ? strtolower($data[$field]) : $data[$field];

                foreach ($this->tokenErrorIndicators as $indicator) {
                    if (is_string($value) && str_contains($value, $indicator)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check if exception is retryable (network/proxy issues)
     */
    private function isRetryableException(\Exception $exception): bool
    {
        $exceptionClass = get_class($exception);
        $message = strtolower($exception->getMessage());

        // Retry on connection errors
        $retryablePatterns = [
            'connection',
            'timeout',
            'timed out',
            'connect() failed',
            'failed to connect',
            'could not connect',
            'network is unreachable',
            'host is down',
            'proxy',
            'ssl',
            'certificate',
        ];

        foreach ($retryablePatterns as $pattern) {
            if (str_contains($message, $pattern)) {
                return true;
            }
        }

        // Retry on specific Guzzle exceptions
        $retryableExceptions = [
            'GuzzleHttp\\Exception\\ConnectException',
            'GuzzleHttp\\Exception\\RequestException',
        ];

        foreach ($retryableExceptions as $retryableException) {
            if ($exceptionClass === $retryableException || is_subclass_of($exceptionClass, $retryableException)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine the delay before retry
     */
    private function retryDelay(): callable
    {
        return function (int $retries) {
            // Exponential backoff with jitter: 1s, 2s, 4s, 8s...
            // Add small random jitter to avoid thundering herd
            $baseDelay = 1000 * pow(2, $retries - 1);
            $jitter = rand(0, 200); // 0-200ms random jitter

            return $baseDelay + $jitter;
        };
    }

    /**
     * Send a GET request
     */
    public function get(string $url, array $options = []): array
    {
        return $this->request('GET', $url, $options);
    }

    /**
     * Send a POST request
     */
    public function post(string $url, array $data = [], array $options = []): array
    {
        $options['json'] = $data;

        return $this->request('POST', $url, $options);
    }

    /**
     * Send a PUT request
     */
    public function put(string $url, array $data = [], array $options = []): array
    {
        $options['json'] = $data;

        return $this->request('PUT', $url, $options);
    }

    /**
     * Send a PATCH request
     */
    public function patch(string $url, array $data = [], array $options = []): array
    {
        $options['json'] = $data;

        return $this->request('PATCH', $url, $options);
    }

    /**
     * Send a DELETE request
     */
    public function delete(string $url, array $options = []): array
    {
        return $this->request('DELETE', $url, $options);
    }

    /**
     * Set the authentication token
     */
    public function withToken(string $token): self
    {
        $this->token = $token;
        $this->initializeClient();

        return $this;
    }

    /**
     * Set custom headers
     */
    public function withHeaders(array $headers): self
    {
        $this->defaultHeaders = array_merge($this->defaultHeaders, $headers);

        return $this;
    }

    /**
     * Enable or disable proxy
     */
    public function withProxy(?string $proxy): self
    {
        $this->proxy = $proxy;
        $this->initializeClient();

        return $this;
    }

    /**
     * Set the token refresh callback
     */
    public function onTokenExpired(callable $callback): self
    {
        $this->tokenRefreshCallback = $callback;

        return $this;
    }

    /**
     * Set the base URI
     */
    public function withBaseUri(string $baseUri): self
    {
        $this->baseUri = $baseUri;
        $this->initializeClient();

        return $this;
    }

    /**
     * Get the last response
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Send a request
     */
    private function request(string $method, string $url, array $options = []): array
    {
        try {
            // Merge default headers
            if (! empty($this->defaultHeaders)) {
                $options['headers'] = array_merge(
                    $this->defaultHeaders,
                    $options['headers'] ?? []
                );
            }

            $response = $this->client->request($method, $url, $options);
            $this->lastResponse = $response;

            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();

            // Try to decode JSON response
            $data = json_decode($body, true) ?? [];

            if ($statusCode >= 400) {
                $this->log('error', 'HTTP request failed', [
                    'method' => $method,
                    'url' => $url,
                    'status' => $statusCode,
                    'response' => $data,
                ]);

                throw new \RuntimeException(
                    "HTTP request failed with status {$statusCode}: ".($data['message'] ?? $body)
                );
            }

            return $data;
        } catch (GuzzleException $e) {
            $this->log('error', 'HTTP request exception', [
                'method' => $method,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('HTTP request failed: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Set max retries for token refresh
     */
    public function setMaxRetries(int $retries): self
    {
        $this->maxRetries = $retries;

        return $this;
    }

    /**
     * Set custom token error indicators
     */
    public function setTokenErrorIndicators(array $indicators): self
    {
        $this->tokenErrorIndicators = $indicators;

        return $this;
    }

    /**
     * Set request timeouts
     */
    public function setTimeouts(int $timeout, int $connectTimeout): self
    {
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;
        $this->initializeClient();

        return $this;
    }

    /**
     * Set custom logger for debugging
     *
     * Compatible with PSR-3 LoggerInterface or any callable with signature:
     * function(string $level, string $message, array $context = []): void
     *
     * @param  callable  $logger  Logger function
     *
     * @example
     * // Using Laravel Log facade
     * $client->setLogger(function($level, $message, $context) {
     *     Log::$level($message, $context);
     * });
     * @example
     * // Using Monolog
     * $client->setLogger(function($level, $message, $context) use ($monolog) {
     *     $monolog->$level($message, $context);
     * });
     * @example
     * // Simple error_log
     * $client->setLogger(function($level, $message, $context) {
     *     error_log("[$level] $message: " . json_encode($context));
     * });
     */
    public function setLogger(callable $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Internal logging method - works without Laravel
     *
     * Falls back to PHP's error_log if no logger is set
     */
    private function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger !== null) {
            // Use custom logger if provided
            call_user_func($this->logger, $level, $message, $context);
        } else {
            // Fallback to native PHP error_log
            $contextString = ! empty($context) ? ' | '.json_encode($context) : '';
            error_log("[HttpClient][$level] $message$contextString");
        }
    }
}
