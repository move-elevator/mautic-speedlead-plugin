<?php

namespace MauticPlugin\SpeedleadBundle\Service;

class FairApiService extends SpeedleadApiService
{
    /**
     * @var array
     */
    private $fair;

    public function callApiShowFair()
    {
        if (null === $this->integration) {
            throw new \Exception('missing speedlead integration.');
        }

        $requestString = sprintf(
            'curl --location --request GET "%s/backend/api/v1/fairs/%s" --header "Authorization: Bearer %s" --header "Content-Type: application/json"',
            $this->getInstance(),
            $this->getFairId(),
            $this->getToken()
        );

        $result = json_decode(exec($requestString), true);

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
