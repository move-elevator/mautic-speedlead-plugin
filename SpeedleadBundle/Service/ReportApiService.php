<?php
declare(strict_types = 1);

namespace MauticPlugin\SpeedleadBundle\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\HttpFoundation\Response;

class ReportApiService extends SpeedleadApiService
{
    public function callApiGetReports(string $createdBeforeString = '-2 hours', string $updatedAfterString = '-4 hours'): array
    {
        if (null === $this->integration) {
            throw new \Exception($this->translator->trans('mautic.speedlead.no_plugin_conf_found'));
        }

        $createdBefore = new \DateTime($createdBeforeString);
        $updatedAfter = new \DateTime($updatedAfterString);

        $client = new Client();

        try {
            $response = $client->request(
                'GET',
                sprintf('%s/backend/api/v1/fairs/%s/survey/reports', $this->getInstance(), $this->getFairId()), [
                    'query' => [
                        'createdBefore' => $createdBefore->getTimestamp(),
                        'updatedAfter' => $updatedAfter->getTimestamp()
                    ],
                    'headers' => [
                        'Authorization' => sprintf('Bearer %s', $this->getToken()),
                        'Content-Type' => 'application/json'
                    ]
                ]
            );
        } catch (ClientException $exception) {
            if (Response::HTTP_UNAUTHORIZED === $exception->getCode()) {
                $this->handleAuthRefresh();

                // call reports-api again with refreshed auth
                return self::callApiGetReports($createdBeforeString, $updatedAfterString);
            }
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}
