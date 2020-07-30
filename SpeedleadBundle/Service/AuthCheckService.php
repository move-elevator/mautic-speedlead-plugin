<?php
declare(strict_types = 1);

namespace MauticPlugin\SpeedleadBundle\Service;

use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationRepository;

class AuthCheckService
{
    /**
     * @var EncryptionHelper
     */
    private $encryptionHelper;

    /**
     * @var IntegrationRepository
     */
    private $integrationsRepository;

    public function __construct(
        IntegrationRepository $integrationsRepository,
        EncryptionHelper $encryptionHelper
    ) {
        $this->encryptionHelper = $encryptionHelper;
        $this->integrationsRepository = $integrationsRepository;
    }

    public function authenticate(array $credentials): array
    {
        if (true === empty($credentials['password'])) {
            $credentials['password'] = $this->getPassword();
        }

        return $this->doLogin($credentials);
    }

    /**
     * @throws \Exception
     */
    private function getPassword(): string
    {
        /** @var Integration $speedleadIntegration */
        $speedleadIntegration = $this->integrationsRepository->findOneBy(['name' => 'Speedlead']);

        if (false === $speedleadIntegration instanceof Integration) {
            throw new \Exception('no speedlead-integration found');
        }

        return $this->encryptionHelper->decrypt($speedleadIntegration->getApiKeys()['password']);
    }

    /**
     * @throws \Exception
     */
    private function doLogin(array $credentials): array
    {
        $requestString = sprintf(
            'curl --location --request POST "%s/backend/api/v1/login" --header "Content-Type: multipart/form-data;" --form "username=%s" --form "password=%s"',
            $credentials['instance'],
            $credentials['username'],
            $credentials['password']
        );

        $response = json_decode(exec($requestString), true);

        if (false === array_key_exists('token', $response)) {
           throw new \Exception(sprintf('login failed with message: %s', $response['message']));
        }

        return $response;
    }
}
