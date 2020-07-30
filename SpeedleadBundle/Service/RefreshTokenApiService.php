<?php
declare(strict_types = 1);

namespace MauticPlugin\SpeedleadBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\PluginBundle\Entity\Integration;

class RefreshTokenApiService
{
    public function refresh(string $baseUrl, string $refreshToken): array
    {
        $requestString = sprintf(
            'curl --location --request POST "%s/backend/api/v1/token/refresh" --header "Content-Type: multipart/form-data;" --form "refresh_token=%s"',
            $baseUrl,
            $refreshToken
        );

        $response = json_decode(exec($requestString), true);


        if (false === array_key_exists('token', $response)) {
            throw new \Exception(sprintf('token refresh failed with message: %s', $response['message']));
        }

        return $response;
    }
}
