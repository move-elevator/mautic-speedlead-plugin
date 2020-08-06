<?php
declare(strict_types = 1);

namespace MauticPlugin\SpeedleadBundle\Service;

use GuzzleHttp\Client;

class FairApiService extends SpeedleadApiService
{
    /**
     * @var array
     */
    private $fair;

    public function callApiShowFair()
    {
        if (null === $this->integration) {
            throw new \Exception($this->translator->trans('mautic.speedlead.no_plugin_conf_found'));
        }

        $client = new Client();

        $response = $client->request(
            'GET',
            sprintf('%s/backend/api/v1/fairs/%s', $this->getInstance(), $this->getFairId()), [
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
            self::callApiShowFair();
        }

        $this->fair = $result;
    }

    public function getFair()
    {
        return $this->fair;
    }
}
