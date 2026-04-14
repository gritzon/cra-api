<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api')]
class ApiController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    #[Route('/check/plugin/{slug}', name: 'check_plugin', methods: ['GET'])]
    public function checkPlugin(string $slug): JsonResponse
    {
        $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));

        // Запрос к WordPress.org API
        $response = $this->httpClient->request('GET',
                                               "https://api.wordpress.org/plugins/info/1.0/{$slug}.json"
        );

        $data = $response->toArray(false);

        if (isset($data['error'])) {
            return $this->json([
                'slug' => $slug,
                'found' => false,
                'cra_status' => 'unknown',
                'message' => 'Plugin not found on WordPress.org'
            ], 404);
        }

        $lastUpdated = isset($data['last_updated'])
        ? strtotime($data['last_updated'])
        : null;

        $monthsOld = $lastUpdated
        ? (time() - $lastUpdated) / (30 * 24 * 3600)
        : null;

        $status = 'ok';
        $issues = [];

        if ($monthsOld === null) {
            $status = 'unknown';
            $issues[] = 'Cannot determine last update date';
        } elseif ($monthsOld > 12) {
            $status = 'risk';
            $issues[] = 'Not updated in over 12 months';
        } elseif ($monthsOld > 6) {
            $status = 'warning';
            $issues[] = 'Not updated in over 6 months';
        }

        return $this->json([
            'slug' => $slug,
            'name' => $data['name'] ?? $slug,
            'version' => $data['version'] ?? null,
            'last_updated' => $data['last_updated'] ?? null,
            'found' => true,
            'cra_status' => $status,
            'issues' => $issues,
            'checked_at' => date('c'),
        ]);
    }

    #[Route('/status', name: 'api_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return $this->json([
            'status' => 'ok',
            'version' => '0.1.0',
            'cra_deadline' => '2026-09-11',
            'days_remaining' => ceil(
                (strtotime('2026-09-11') - time()) / 86400
            ),
        ]);
    }

    #[Route('/check/crate/{name}', name: 'check_crate', methods: ['GET'])]
    public function checkCrate(string $name): JsonResponse
    {
        $name = preg_replace('/[^a-z0-9\-_]/', '', strtolower($name));

        // Запрос к crates.io API
        $response = $this->httpClient->request('GET',
                                               "https://crates.io/api/v1/crates/{$name}",
                                               [
                                                   'headers' => [
                                                       'User-Agent' => 'cra-api/0.1.0 (https://github.com/gritzon)'
                                                   ]
                                               ]
        );

        if ($response->getStatusCode() === 404) {
            return $this->json([
                'name' => $name,
                'found' => false,
                'cra_status' => 'unknown',
                'message' => 'Crate not found on crates.io'
            ], 404);
        }

        $data = $response->toArray(false);
        $crate = $data['crate'] ?? [];

        $lastUpdated = isset($crate['updated_at'])
        ? strtotime($crate['updated_at'])
        : null;

        $monthsOld = $lastUpdated
        ? (time() - $lastUpdated) / (30 * 24 * 3600)
        : null;

        $status = 'ok';
        $issues = [];

        if ($monthsOld === null) {
            $status = 'unknown';
            $issues[] = 'Cannot determine last update date';
        } elseif ($monthsOld > 12) {
            $status = 'risk';
            $issues[] = 'Not updated in over 12 months — CRA compliance risk';
        } elseif ($monthsOld > 6) {
            $status = 'warning';
            $issues[] = 'Not updated in over 6 months — monitor closely';
        }

        if (empty($crate['repository'])) {
            $status = $status === 'ok' ? 'warning' : $status;
            $issues[] = 'No repository URL — transparency requirement under CRA';
        }

        return $this->json([
            'name' => $name,
            'found' => true,
            'version' => $crate['newest_version'] ?? null,
            'updated_at' => $crate['updated_at'] ?? null,
            'downloads' => $crate['downloads'] ?? null,
            'repository' => $crate['repository'] ?? null,
            'cra_status' => $status,
            'issues' => $issues,
            'checked_at' => date('c'),
        ]);
    }
}
