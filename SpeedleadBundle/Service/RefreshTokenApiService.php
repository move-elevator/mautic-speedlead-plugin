<?php
declare(strict_types = 1);

namespace MauticPlugin\SpeedleadBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\PluginBundle\Entity\Integration;

class RefreshTokenApiService
{
    public function refresh(string $baseUrl, string $refreshToken): array
    {
        $client = new Client();

        $response = $client->request(
            'POST',
            sprintf('%s/backend/api/v1/token/refresh', $baseUrl), [
                'multipart' => [
                    ['name' => 'refresh_token', 'contents' => $refreshToken],
                ]
            ]
        );

        $responseTokens = json_decode($response->getBody()->getContents(), true);

        if (false === array_key_exists('token', $responseTokens)) {
            throw new \Exception(sprintf('token refresh failed with message: %s', $responseTokens['message']));
        }

        return $responseTokens;
    }
}
