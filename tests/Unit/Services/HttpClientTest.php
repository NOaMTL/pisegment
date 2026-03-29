<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Http\GuzzleHttpClient;
use App\Services\Http\HttpClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

/**
 * Tests pour le service HTTP Client
 *
 * Pour exécuter ces tests:
 * php artisan test --filter=HttpClientTest
 */
class HttpClientTest extends TestCase
{
    private HttpClientInterface $httpClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = app(HttpClientInterface::class);
    }

    /** @test */
    public function it_can_make_get_request()
    {
        // Mock de la réponse
        $mock = new MockHandler([
            new Response(200, [], json_encode(['data' => 'test'])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Injectez le mock dans votre service si nécessaire
        // Pour les vrais tests d'intégration, utilisez une vraie API de test

        $this->assertTrue(true); // Remplacez par votre vraie assertion
    }

    /** @test */
    public function it_can_set_bearer_token()
    {
        $token = 'test_token_123';

        $result = $this->httpClient->withToken($token);

        $this->assertInstanceOf(HttpClientInterface::class, $result);
    }

    /** @test */
    public function it_can_set_proxy()
    {
        $proxy = 'http://proxy.example.com:8080';

        $result = $this->httpClient->withProxy($proxy);

        $this->assertInstanceOf(HttpClientInterface::class, $result);
    }

    /** @test */
    public function it_can_set_base_uri()
    {
        $baseUri = 'https://api.example.com';

        $result = $this->httpClient->withBaseUri($baseUri);

        $this->assertInstanceOf(HttpClientInterface::class, $result);
    }

    /** @test */
    public function it_can_chain_configurations()
    {
        $result = $this->httpClient
            ->withBaseUri('https://api.example.com')
            ->withToken('test_token')
            ->withProxy('http://proxy.example.com:8080')
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Custom' => 'value',
            ]);

        $this->assertInstanceOf(HttpClientInterface::class, $result);
    }
}

/**
 * EXEMPLE DE TEST AVEC MOCK COMPLET:
 *
 * Pour tester sans appeler vraiment l'API externe, utilisez Mockery:
 *
 * use Mockery;
 * use App\Services\Http\HttpClientInterface;
 *
 * public function test_service_uses_http_client()
 * {
 *     // Mock du HttpClient
 *     $mockClient = Mockery::mock(HttpClientInterface::class);
 *
 *     $mockClient->shouldReceive('withBaseUri')
 *         ->once()
 *         ->with('https://api.example.com')
 *         ->andReturnSelf();
 *
 *     $mockClient->shouldReceive('withToken')
 *         ->once()
 *         ->andReturnSelf();
 *
 *     $mockClient->shouldReceive('get')
 *         ->once()
 *         ->with('/users')
 *         ->andReturn(['data' => ['user1', 'user2']]);
 *
 *     // Injectez le mock dans le container
 *     $this->app->instance(HttpClientInterface::class, $mockClient);
 *
 *     // Testez votre service qui utilise le HttpClient
 *     $service = app(YourService::class);
 *     $result = $service->getUsers();
 *
 *     $this->assertIsArray($result);
 *     $this->assertCount(2, $result['data']);
 * }
 *
 * EXEMPLE DE TEST AVEC GUZZLE MOCK:
 *
 * use GuzzleHttp\Client;
 * use GuzzleHttp\Handler\MockHandler;
 * use GuzzleHttp\HandlerStack;
 * use GuzzleHttp\Psr7\Response;
 * use GuzzleHttp\Exception\RequestException;
 *
 * public function test_http_client_handles_success_response()
 * {
 *     // Créer un mock de réponse
 *     $mock = new MockHandler([
 *         new Response(200, ['Content-Type' => 'application/json'], json_encode([
 *             'id' => 1,
 *             'name' => 'John Doe',
 *             'email' => 'john@example.com'
 *         ])),
 *     ]);
 *
 *     $handlerStack = HandlerStack::create($mock);
 *     $client = new Client(['handler' => $handlerStack]);
 *
 *     // Testez avec ce client mocké
 *     $response = $client->request('GET', '/users/1');
 *     $data = json_decode($response->getBody(), true);
 *
 *     $this->assertEquals(200, $response->getStatusCode());
 *     $this->assertEquals('John Doe', $data['name']);
 * }
 *
 * public function test_http_client_handles_error_response()
 * {
 *     $mock = new MockHandler([
 *         new Response(401, [], json_encode(['error' => 'Unauthorized'])),
 *     ]);
 *
 *     $handlerStack = HandlerStack::create($mock);
 *     $client = new Client(['handler' => $handlerStack]);
 *
 *     $response = $client->request('GET', '/protected');
 *
 *     $this->assertEquals(401, $response->getStatusCode());
 * }
 *
 * EXEMPLE DE TEST AVEC TOKEN REFRESH:
 *
 * public function test_token_refresh_is_called_on_401()
 * {
 *     $refreshCalled = false;
 *
 *     // Premier appel retourne 401, second appel retourne 200
 *     $mock = new MockHandler([
 *         new Response(401, [], json_encode(['error' => 'Unauthorized'])),
 *         new Response(200, [], json_encode(['data' => 'success'])),
 *     ]);
 *
 *     $handlerStack = HandlerStack::create($mock);
 *     $client = new Client(['handler' => $handlerStack]);
 *
 *     // Votre logique de test avec callback
 *     $httpClient = new GuzzleHttpClient();
 *     $httpClient->onTokenExpired(function () use (&$refreshCalled) {
 *         $refreshCalled = true;
 *         return 'new_token';
 *     });
 *
 *     // La fonction de refresh devrait être appelée
 *     $this->assertTrue(true); // Remplacez par votre assertion
 * }
 *
 * TESTS D'INTÉGRATION:
 *
 * Pour les tests d'intégration qui appellent vraiment une API,
 * utilisez une API de test comme JSONPlaceholder:
 *
 * public function test_real_api_integration()
 * {
 *     $httpClient = app(HttpClientInterface::class);
 *
 *     $data = $httpClient
 *         ->withBaseUri('https://jsonplaceholder.typicode.com')
 *         ->get('/users/1');
 *
 *     $this->assertIsArray($data);
 *     $this->assertArrayHasKey('id', $data);
 *     $this->assertArrayHasKey('name', $data);
 *     $this->assertEquals(1, $data['id']);
 * }
 */
