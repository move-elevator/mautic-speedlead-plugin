<?php
declare(strict_types = 1);

namespace MauticPlugin\SpeedleadBundle\Service;

use GuzzleHttp\Client;
use Symfony\Component\Translation\TranslatorInterface;

class RefreshTokenApiService
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

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
            throw new \Exception($this->translator->trans('mautic.speedlead.token_refresh_failed_with_msg', ['%message%' => $responseTokens['message']]));
        }

        return $responseTokens;
    }
}
