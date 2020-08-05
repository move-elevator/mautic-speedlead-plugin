<?php
declare(strict_types = 1);

namespace MauticPlugin\SpeedleadBundle\Service;

use GuzzleHttp\Client;

class ReportApiService extends SpeedleadApiService
{
    public function callApiGetReports(string $createdBeforeString = '-2 hours', string $updatedAfterString = '-4 hours'): array
    {
        if (null === $this->integration) {
            throw new \Exception('missing speedlead integration.');
        }

        $createdBefore = new \DateTime($createdBeforeString);
        $updatedAfter = new \DateTime($updatedAfterString);

        $client = new Client();

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

        $result = json_decode($response->getBody()->getContents(), true);

        if (true === array_key_exists('code', $result) && $result['code'] === 401) {
            $this->handleAuthRefresh();

            // call reports-api again with refreshed auth
            return self::callApiGetReports($createdBeforeString, $updatedAfterString);
        }

        return $result;
    }


}
