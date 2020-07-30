<?php
declare(strict_types = 1);

namespace MauticPlugin\SpeedleadBundle\Service;

class ReportApiService extends SpeedleadApiService
{
    public function callApiGetReports(string $createdBeforeString = '-2 hours', string $updatedAfterString = '-4 hours'): array
    {
        if (null === $this->integration) {
            throw new \Exception('missing speedlead integration.');
        }

        $createdBefore = new \DateTime($createdBeforeString);
        $updatedAfter = new \DateTime($updatedAfterString);

        $requestString = sprintf(
            'curl --location --request GET "%s/backend/api/v1/fairs/%s/survey/reports?createdBefore=%s&updatedAfter=%s" --header "Authorization: Bearer %s" --header "Content-Type: application/json"',
            $this->getInstance(),
            $this->getFairId(),
            $createdBefore->getTimestamp(),
            $updatedAfter->getTimestamp(),
            $this->getToken()
        );

        $result = json_decode(exec($requestString), true);

        if (true === array_key_exists('code', $result) && $result['code'] === 401) {
            $this->handleAuthRefresh();

            // call reports-api again with refreshed auth
            return self::callApiGetReports($createdBeforeString, $updatedAfterString);
        }

        return $result;
    }


}
