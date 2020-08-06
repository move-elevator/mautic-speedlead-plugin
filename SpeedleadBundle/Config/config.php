<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'speedlead',
    'description' => 'bundle for speedlead integration.',
    'version'     => '1.0',
    'author'      => 'pk',
    'routes' => [
        'main' => [
            'mautic_speedlead_contact_import' => [
                'path' => '/speedlead/contact/import',
                'controller' => 'SpeedleadBundle:Contact:import',
            ]
        ],
    ],
    'menu' => [
        'main' => [
            'mautic.speedlead.index' => [
                'iconClass' => 'fa-globe',
                'children' => [],
                'priority' => 0
            ],
            'mautic.speedlead.contact.import' => [
                'route' => 'mautic_speedlead_contact_import',
                'iconClass' => 'fa-globe',
                'children' => [],
                'parent' => 'mautic.speedlead.index'
            ]
        ]
    ],
    'services'    => [
        'integrations' => [
            'mautic.integration.speedlead' => [
                'class' => \MauticPlugin\SpeedleadBundle\Integration\SpeedleadIntegration::class,
                'arguments' => [],
            ],
        ],
        'command' => [
            'mautic.speedlead.command.import_contacts' => [
                'class' => \MauticPlugin\SpeedleadBundle\Command\ImportContactsCommand::class,
                'arguments' => [
                    '@=service("doctrine").getRepository("MauticPluginBundle:Integration")',
                    'mautic.speedlead.service.report_api',
                    'mautic.speedlead.service.report_contact_mapper'
                ],
            ],
        ],
        'others' => [
            'mautic.speedlead.service.authcheck' => [
                'class' => \MauticPlugin\SpeedleadBundle\Service\AuthCheckService::class,
                'arguments' => [
                    '@=service("doctrine").getRepository("MauticPluginBundle:Integration")',
                    'mautic.helper.encryption',
                    'translator'
                ],
            ],
            'mautic.speedlead.service.refresh_token_api' => [
                'class' => \MauticPlugin\SpeedleadBundle\Service\RefreshTokenApiService::class,
                'arguments' => [
                    'translator'
                ]
            ],
            'mautic.speedlead.service.report_api' => [
                'class' => \MauticPlugin\SpeedleadBundle\Service\ReportApiService::class,
                'arguments' => [
                    '@=service("doctrine").getRepository("MauticPluginBundle:Integration")',
                    'mautic.helper.encryption',
                    'mautic.speedlead.service.refresh_token_api',
                    'translator'
                ]
            ],
            'mautic.speedlead.service.fair_api' => [
                'class' => \MauticPlugin\SpeedleadBundle\Service\FairApiService::class,
                'arguments' => [
                    '@=service("doctrine").getRepository("MauticPluginBundle:Integration")',
                    'mautic.helper.encryption',
                    'mautic.speedlead.service.refresh_token_api',
                    'translator'
                ]
            ],
            'mautic.speedlead.service.report_contact_mapper' => [
                'class' => \MauticPlugin\SpeedleadBundle\Service\ReportContactMapperService::class,
                'arguments' => [
                    'mautic.model.factory',
                    'mautic.helper.ip_lookup',
                    '@=service("doctrine").getRepository("MauticLeadBundle:Lead")',
                    '@=service("doctrine").getRepository("MauticStageBundle:Stage")',
                    '@=service("doctrine").getRepository("MauticLeadBundle:LeadList")',
                    '@=service("doctrine").getRepository("MauticLeadBundle:ListLead")',
                    '@=service("doctrine").getRepository("MauticLeadBundle:Company")',
                    'mautic.speedlead.service.fair_api',
                    'mautic.speedlead.service.url_generator',
                    '@=service("doctrine").getRepository("MauticLeadBundle:LeadEventLog")',
                    'translator'
                ]
            ],
            'mautic.speedlead.service.url_generator' => [
                'class' => \MauticPlugin\SpeedleadBundle\Service\UrlGeneratorService::class
            ],
        ],
    ],
];
