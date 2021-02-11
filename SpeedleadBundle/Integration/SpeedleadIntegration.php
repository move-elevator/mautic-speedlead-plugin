<?php
declare(strict_types = 1);

namespace MauticPlugin\SpeedleadBundle\Integration;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\PluginBundle\Model\IntegrationEntityModel;
use Mautic\StageBundle\Entity\Stage;
use MauticPlugin\SpeedleadBundle\Service\AuthCheckService;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

class SpeedleadIntegration extends AbstractIntegration
{
    /**
     * @var AuthCheckService
     */
    private $authCheckService;

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Speedlead';
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayName(): string
    {
        return 'speedlead';
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthenticationType(): string
    {
        return 'bearertoken';
    }

    /**
     * {@inheritdoc}
     */
    public function getClientSecretKey(): string
    {
        return 'password';
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredKeyFields(): array
    {
        return [
            'instance' => 'mautic.integration.speedlead.instance',
            'fair' => 'mautic.integration.speedlead.fair',
            'username' => 'mautic.integration.speedlead.username',
            'password' => 'mautic.integration.speedlead.password',
        ];
    }

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        CacheStorageHelper $cacheStorageHelper,
        EntityManager $entityManager,
        Session $session,
        RequestStack $requestStack,
        Router $router,
        TranslatorInterface $translator,
        Logger $logger,
        EncryptionHelper $encryptionHelper,
        LeadModel $leadModel,
        CompanyModel $companyModel,
        PathsHelper $pathsHelper,
        NotificationModel $notificationModel,
        FieldModel $fieldModel,
        IntegrationEntityModel $integrationEntityModel,
        DoNotContactModel $doNotContact,
        AuthCheckService $authCheckService
    ) {
        $this->authCheckService = $authCheckService;

        parent::__construct(
            $eventDispatcher,
            $cacheStorageHelper,
            $entityManager,
            $session,
            $requestStack,
            $router,
            $translator,
            $logger,
            $encryptionHelper,
            $leadModel,
            $companyModel,
            $pathsHelper,
            $notificationModel,
            $fieldModel,
            $integrationEntityModel,
            $doNotContact
        );
    }

    /**
     * {@inheritdoc}
     */
    public function appendToForm(&$builder, $data, $formArea): void
    {
        $stages = $this->em->getRepository(Stage::class)->getStages();
        $stageChoices = ['mautic.integration.speedlead.initial.stage.none' => 0];

        foreach ($stages as $stage) {
            $stageChoices[$stage['name']] = $stage['id'];
        }

        $segments = $this->em->getRepository(LeadList::class)->getGlobalLists();
        $segmentChoices = [];

        foreach ($segments as $segment) {
            $segmentChoices[$segment['name']] = $segment['id'];
        }

        if ($formArea === 'features') {
            $builder->add(
                'initialStage',
                ChoiceType::class,
                [
                    'choices'    => $stageChoices,
                    'label'      => 'mautic.integration.speedlead.initial.stage',
                    'multiple'   => false,
                    'required'   => true,
                ]
            );

            $builder->add(
                'segments',
                ChoiceType::class,
                [
                    'choices'    => $segmentChoices,
                    'label'      => 'mautic.integration.speedlead.segment',
                    'multiple'   => true,
                    'required'   => true,
                ]
            );

            $builder->add(
                'automaticImport',
                ChoiceType::class,
                [
                    'choices'    => [
                        'mautic.integration.speedlead.automatic.import.no' => 0,
                        'mautic.integration.speedlead.automatic.import.yes' => 1,
                    ],
                    'label'      => 'mautic.integration.speedlead.automatic.import',
                    'required'   => true,
                ]
            );

        }

        if ($formArea === 'keys') {
            $builder->add('token', HiddenType::class);
            $builder->add('refresh_token', HiddenType::class);

            $builder->addEventListener(
                FormEvents::PRE_SUBMIT,
                [$this, 'onPreSubmit']
            );
        }
    }

    /**
     * @throws \Exception
     */
    public function onPreSubmit(FormEvent $event): void
    {
        try {
            $authResponse = $this->authCheckService->authenticate($event->getData());

            $updatedApiKeys = $event->getData();
            $updatedApiKeys['token'] = $authResponse['token'];
            $updatedApiKeys['refresh_token'] = $authResponse['refresh_token'];

            $event->setData($updatedApiKeys);
        } catch (\Exception $exception) {
            throw $exception;
        }
    }
}
