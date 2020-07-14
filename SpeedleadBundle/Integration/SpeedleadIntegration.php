<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\SpeedleadBundle\Integration;

use Mautic\LeadBundle\Entity\LeadList;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\StageBundle\Entity\Stage;
use MauticPlugin\SpeedleadBundle\Service\AuthCheckService;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class SpeedleadIntegration extends AbstractIntegration
{
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

    /**
     * {@inheritdoc}
     */
    public function appendToForm(&$builder, $data, $formArea): void
    {
        $stages = $this->em->getRepository(Stage::class)->getStages();
        $stageChoices = [0 => 'mautic.integration.speedlead.initial.stage.none'];

        foreach ($stages as $stage) {
            $stageChoices[$stage['id']] = $stage['name'];
        }

        $segments = $this->em->getRepository(LeadList::class)->getGlobalLists();
        $segmentChoices = [0 => 'mautic.integration.speedlead.segment.none'];

        foreach ($segments as $segment) {
            $segmentChoices[$segment['id']] = $segment['name'];
        }

        if ($formArea == 'features') {
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
                'segment',
                ChoiceType::class,
                [
                    'choices'    => $segmentChoices,
                    'label'      => 'mautic.integration.speedlead.segment',
                    'multiple'   => false,
                    'required'   => true,
                ]
            );

            $builder->add(
                'automaticImport',
                ChoiceType::class,
                [
                    'choices'    => [
                        0 => 'mautic.integration.speedlead.automatic.import.no',
                        1 => 'mautic.integration.speedlead.automatic.import.yes',
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
            /** @var AuthCheckService $loginService */
            $loginService = $this->factory->get('mautic.speedlead.service.authcheck');

            $authResponse = $loginService->authenticate($event->getData());

            $updatedApiKeys = $event->getData();
            $updatedApiKeys['token'] = $authResponse['token'];
            $updatedApiKeys['refresh_token'] = $authResponse['refresh_token'];

            $event->setData($updatedApiKeys);
        } catch (\Exception $exception) {
            throw $exception;
        }
    }
}
