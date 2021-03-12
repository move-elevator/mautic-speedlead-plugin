<?php
declare(strict_types = 1);

namespace MauticPlugin\SpeedleadBundle\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\HttpFoundation\Response;

class SurveyApiService extends SpeedleadApiService
{
    public function callApiGetSurvey(): array
    {
        if (null === $this->integration) {
            throw new \Exception($this->translator->trans('mautic.speedlead.no_plugin_conf_found'));
        }

        $client = new Client();

        try {
            $response = $client->request(
                'GET',
                sprintf('%s/backend/api/v1/fairs/%s/survey', $this->getInstance(), $this->getFairId()), [
                    'headers' => [
                        'Authorization' => sprintf('Bearer %s', $this->getToken()),
                        'Content-Type' => 'application/json'
                    ]
                ]
            );
        } catch (ClientException $exception) {
            if (Response::HTTP_UNAUTHORIZED === $exception->getCode()) {
                $this->handleAuthRefresh();

                // call api again with refreshed auth
                return self::callApiGetSurvey();
            }
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}
