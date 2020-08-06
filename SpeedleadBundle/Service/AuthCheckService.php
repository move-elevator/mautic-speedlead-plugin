<?php
declare(strict_types = 1);

namespace MauticPlugin\SpeedleadBundle\Service;

use GuzzleHttp\Client;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationRepository;
use Symfony\Component\Translation\TranslatorInterface;

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

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        IntegrationRepository $integrationsRepository,
        EncryptionHelper $encryptionHelper,
        TranslatorInterface $translator
    ) {
        $this->encryptionHelper = $encryptionHelper;
        $this->integrationsRepository = $integrationsRepository;
        $this->translator = $translator;
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
            throw new \Exception($this->translator->trans('mautic.speedlead.no_plugin_conf_found'));
        }

        return $this->encryptionHelper->decrypt($speedleadIntegration->getApiKeys()['password']);
    }

    /**
     * @throws \Exception
     */
    private function doLogin(array $credentials): array
    {
        $client = new Client();

        $response = $client->request(
            'POST',
            sprintf('%s/backend/api/v1/login', $credentials['instance']), [
                'multipart' => [
                    ['name' => 'username', 'contents' => $credentials['username']],
                    ['name' => 'password', 'contents' => $credentials['password']],
                ]
            ]
        );

        $responseTokens = json_decode($response->getBody()->getContents(), true);

        if (false === array_key_exists('token', $responseTokens)) {
           throw new \Exception($this->translator->trans('mautic.speedlead.sl_login_failed_with_msg', ['%message%' => $responseTokens['message']]));
        }

        return $responseTokens;
    }
}
