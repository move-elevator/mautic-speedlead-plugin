<?php
declare(strict_types = 1);

namespace MauticPlugin\SpeedleadBundle\Service;

use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationRepository;

class SpeedleadApiService
{
    /**
     * @var EncryptionHelper
     */
    protected $encryptionHelper;

    /**
     * @var null|Integration
     */
    protected $integration;

    /**
     * @var RefreshTokenApiService
     */
    private $refreshTokenApiService;

    /**
     * @var IntegrationRepository
     */
    private $integrationsRepository;

    public function __construct(
        IntegrationRepository $integrationRepository,
        EncryptionHelper $encryptionHelper,
        RefreshTokenApiService $refreshTokenApiService
    ) {
        $this->integrationsRepository = $integrationRepository;
        $this->encryptionHelper = $encryptionHelper;
        $this->refreshTokenApiService = $refreshTokenApiService;

        $this->integration = $this->integrationsRepository->findOneBy(['name' => 'Speedlead']);
    }

    protected function handleAuthRefresh(): void
    {
        // refresh the token if expired
        $refreshedTokenResponse = $this->refreshTokenApiService->refresh(
            $this->getInstance(),
            $this->getRefreshToken()
        );

        // save refreshed token to apiKeys
        $apiKeys = $this->integration->getApiKeys();
        $apiKeys['token'] = $this->encryptionHelper->encrypt($refreshedTokenResponse['token']);
        $this->integration->setApiKeys($apiKeys);
        $this->integrationsRepository->saveEntity($this->integration);
    }

    public function getInstance(): string
    {
        return $this->encryptionHelper->decrypt($this->integration->getApiKeys()['instance']);
    }

    public function getFairId(): string
    {
        return $this->encryptionHelper->decrypt($this->integration->getApiKeys()['fair']);
    }

    protected function getRefreshToken(): string
    {
        return $this->encryptionHelper->decrypt($this->integration->getApiKeys()['refresh_token']);
    }

    protected function getToken(): string
    {
        return $this->encryptionHelper->decrypt($this->integration->getApiKeys()['token']);
    }
}
