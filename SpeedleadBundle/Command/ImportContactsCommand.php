<?php

namespace MauticPlugin\SpeedleadBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\SpeedleadBundle\Service\ReportApiService;
use MauticPlugin\SpeedleadBundle\Service\ReportContactMapperService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
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

    public function __construct(
        IntegrationRepository $integrationRepository,
        ReportApiService $reportApiService,
        ReportContactMapperService $reportContactMapper
    ) {
        $this->integrationsRepository = $integrationRepository;
        $this->reportApiService = $reportApiService;
        $this->reportContactMapper = $reportContactMapper;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('speedlead:import-contacts')
            ->setDescription('Import contacts from configured speedlead-instance.');
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
            //get reports
            $reports = $this->reportApiService->callApiGetReports();

            // map reports
            foreach($reports as $report) {
                $this->reportContactMapper->createContact($report, $integration->getFeatureSettings());

                $output->writeln(sprintf('finished handling for report: %s', $report['id']));
            }
        } catch (\Throwable $exception) {
            $output->writeln(sprintf('command failed with message: %s', $exception->getMessage()));
        }
    }
}
