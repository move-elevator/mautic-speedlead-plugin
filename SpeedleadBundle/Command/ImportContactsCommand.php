<?php
declare(strict_types = 1);

namespace MauticPlugin\SpeedleadBundle\Command;

use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\SpeedleadBundle\Repository\SurveyConfigurationRepository;
use MauticPlugin\SpeedleadBundle\Service\ReportApiService;
use MauticPlugin\SpeedleadBundle\Service\ReportContactMapperService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \Mautic\PluginBundle\Entity\IntegrationRepository;

class ImportContactsCommand extends ContainerAwareCommand
{
    /**
     * @var IntegrationRepository
     */
    private $integrationsRepository;

    /**
     * @var ReportApiService
     */
    private $reportApiService;

    /**
     * @var ReportContactMapperService
     */
    private $reportContactMapper;

    /**
     * @var SurveyConfigurationRepository
     */
    private $surveyConfigurationRepository;

    /**
     * @var EncryptionHelper
     */
    private $encryptionHelper;

    public function __construct(
        IntegrationRepository $integrationRepository,
        ReportApiService $reportApiService,
        ReportContactMapperService $reportContactMapper,
        SurveyConfigurationRepository $surveyConfigurationRepository,
        EncryptionHelper $encryptionHelper
    ) {
        $this->integrationsRepository = $integrationRepository;
        $this->reportApiService = $reportApiService;
        $this->reportContactMapper = $reportContactMapper;
        $this->surveyConfigurationRepository = $surveyConfigurationRepository;
        $this->encryptionHelper = $encryptionHelper;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('speedlead:import-contacts')
            ->setDescription('Import contacts from configured speedlead-instance.')
            ->addOption(
                'createdBefore',
                'c',
                InputOption::VALUE_OPTIONAL,
                'only get reports that were created before given string.',
                'now'
            )
            ->addOption(
                'updatedAfter',
                'u',
                InputOption::VALUE_OPTIONAL,
                'only get reports that were updated after given string.',
                'now'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Integration $integration */
        $integration = $this->integrationsRepository->findOneBy(['name' => 'Speedlead']);

        if (false === $integration instanceof Integration) {
            $output->writeln('no plugin-configurtaion found for speedlead');

            return;
        }

        if (true === empty($integration->getFeatureSettings())) {
            $output->writeln('plugin-configuration found but feature-settings are not configured - aborting...');

            return;
        }

        if (false === (bool)$integration->getFeatureSettings()['automaticImport']) {
            $output->writeln('automatic import disabled - aborting...');

            return;
        }

        try {
            $surveyConfiguration = $this->surveyConfigurationRepository->findOneBy(['fairId' => $this
                ->encryptionHelper
                ->decrypt($integration->getApiKeys()['fair'])
            ]);

            //get reports
            $reports = $this->reportApiService->callApiGetReports(
                $input->getOption('createdBefore'),
                $input->getOption('updatedAfter')
            );

            // map reports
            foreach($reports as $report) {
                $this->reportContactMapper->createContact($report, $integration->getFeatureSettings(), $surveyConfiguration);

                $output->writeln(sprintf('finished handling for report: %s', $report['id']));
            }
        } catch (\Throwable $exception) {
            $output->writeln(sprintf('command failed with message: %s', $exception->getMessage()));
        }
    }
}
