<?php
namespace App\Services;

use GuzzleHttp\Client;
use App\Services\SettingsService;

class HaloService
{
    protected string $baseUrl;
    protected string $authUrl;
    protected string $tenant;
    protected string $clientId;
    protected string $clientSecret;
    protected ?string $accessToken = null;
    protected Client $client;

    public function __construct(protected SettingsService $settings)
    {
        $this->baseUrl       = rtrim($settings->get('halo_api_resource_url'), '/');
        $this->authUrl       = rtrim($settings->get('halo_api_auth_url'), '/');
        $this->tenant        = $settings->get('halo_api_tenant');
        $this->clientId      = $settings->get('halo_api_client_id');
        $this->clientSecret  = $settings->get('halo_api_client_secret');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 15.0,
        ]);
    }

    protected function authenticate(): void
    {
        if ($this->accessToken) return;

        $response = (new Client())->post("{$this->authUrl}/token", [
            'form_params' => [
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'tenant'        => $this->tenant,
                'scope'         => 'all'
            ]
        ]);

        $body = json_decode((string)$response->getBody(), true);
        $this->accessToken = $body['access_token'] ?? null;
    }

    protected function headers(): array
    {
        $this->authenticate();

        return [
            'Authorization' => "Bearer {$this->accessToken}",
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];
    }

    public function get(string $uri, array $query = []): array
    {
        $response = $this->client->get($uri, [
            'headers' => $this->headers(),
            'query' => $query,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function post(string $uri, array $data): array
    {
        $response = $this->client->post($uri, [
            'headers' => $this->headers(),
            'json' => $data,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function put(string $uri, array $data): array
    {
        $response = $this->client->put($uri, [
            'headers' => $this->headers(),
            'json' => $data,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function delete(string $uri): array
    {
        $response = $this->client->delete($uri, [
            'headers' => $this->headers(),
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
