<?php
namespace App\Services;

use App\Services\SettingsService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class XeroService
{
    protected Client $client;
    protected string $accessToken;
    protected string $tenantId;

    public function __construct(protected SettingsService $settings)
    {
        $this->accessToken = $settings->get('xero_access_token');
        $this->tenantId    = $settings->get('xero_tenant_id');

        $this->client = new Client([
            'base_uri' => 'https://api.xero.com/payroll.xro/2.0/', // AU Payroll API
            'timeout'  => 15.0,
        ]);
    }

    public function getAuthorizeUrl(): string
    {
        $clientId = $this->settings->get('xero_client_id');
        $redirectUri = urlencode($this->settings->get('xero_redirect_uri'));
        $scope = urlencode('openid profile email offline_access accounting.transactions payroll.employees payroll.timesheets payroll.payruns');

        return "https://login.xero.com/identity/connect/authorize?response_type=code&client_id={$clientId}&redirect_uri={$redirectUri}&scope={$scope}";
    }

    public function connectTenant(): array
    {
        $response = $this->client->get('https://api.xero.com/connections', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->settings->get('xero_access_token'),
                'Accept' => 'application/json',
            ],
        ]);

        $data = json_decode((string)$response->getBody(), true);

        // Take the first connection (or prompt user to choose)
        if (isset($data[0]['tenantId'])) {
            $this->settings->set('xero_tenant_id', $data[0]['tenantId']);
        }

        return $data;
    }

    public function exchangeCodeForToken(string $code): array
    {
        $clientId = $this->settings->get('xero_client_id');
        $clientSecret = $this->settings->get('xero_client_secret');
        $redirectUri = $this->settings->get('xero_redirect_uri');

        $response = $this->client->post('https://identity.xero.com/connect/token', [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ],
            'headers' => ['Accept' => 'application/json'],
        ]);

        $data = json_decode((string)$response->getBody(), true);

        // Store tokens
        $this->settings->set('xero_access_token', $data['access_token']);
        $this->settings->set('xero_refresh_token', $data['refresh_token']);

        return $data;
    }

    public function refreshAccessToken(): void
    {
        $clientId = $this->settings->get('xero_client_id');
        $clientSecret = $this->settings->get('xero_client_secret');
        $refreshToken = $this->settings->get('xero_refresh_token');

        $response = $this->client->post('https://identity.xero.com/connect/token', [
            'form_params' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ],
            'headers' => ['Accept' => 'application/json'],
        ]);

        $data = json_decode((string)$response->getBody(), true);
        $this->settings->set('xero_access_token', $data['access_token']);
        $this->settings->set('xero_refresh_token', $data['refresh_token']);
    }

    protected function headers(): array
    {
        return [
            'Authorization'      => "Bearer {$this->accessToken}",
            'Accept'             => 'application/json',
            'Xero-tenant-id'     => $this->tenantId,
            'Content-Type'       => 'application/json',
        ];
    }

    public function get(string $endpoint, array $query = []): array
    {
        try {
            $response = $this->client->get($endpoint, [
                'headers' => $this->headers(),
                'query'   => $query,
            ]);
            return json_decode((string)$response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    public function post(string $endpoint, array $data): array
    {
        try {
            $response = $this->client->post($endpoint, [
                'headers' => $this->headers(),
                'json'    => $data,
            ]);
            return json_decode((string)$response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    public function put(string $endpoint, array $data): array
    {
        try {
            $response = $this->client->put($endpoint, [
                'headers' => $this->headers(),
                'json'    => $data,
            ]);
            return json_decode((string)$response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    protected function handleException(RequestException $e): array
    {
        return [
            'error' => true,
            'message' => $e->getMessage(),
            'response' => $e->getResponse()?->getBody()->getContents()
        ];
    }
}

