<?php
declare(strict_types = 1);

namespace MauticPlugin\SpeedleadBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\PluginBundle\Entity\Integration;

class ContactController extends AbstractFormController
{
    public function importAction()
    {
        $integrationRepository = $this
            ->getDoctrine()
            ->getRepository(Integration::class);

        /** @var Integration $integration */
        $integration = $integrationRepository->findOneBy(['name' => 'Speedlead']);

        if (false === $integration instanceof Integration) {
            return $this->delegateView([
                'contentTemplate' => 'SpeedleadBundle:Contact:import.html.php',
                'viewParameters' => [
                    'message' => 'no plugin-configurtaion found for speedlead'
                ]
            ]);
        }

        if (true === empty($integration->getFeatureSettings())) {
            return $this->delegateView([
                'contentTemplate' => 'SpeedleadBundle:Contact:import.html.php',
                'viewParameters' => [
                    'message' => 'plugin-configuration found but feature-settings are not configured'
                ]
            ]);
        }

        try {
            //get reports
            $reports = $this->get('mautic.speedlead.service.report_api')->callApiGetReports();

            // map reports
            foreach($reports as $report) {
                $this
                    ->get('mautic.speedlead.service.report_contact_mapper')
                    ->createContact($report, $integration->getFeatureSettings());
            }
        } catch (\Throwable $exception) {
            return $this->delegateView([
                'contentTemplate' => 'SpeedleadBundle:Contact:import.html.php',
                'viewParameters' => [
                    'message' => sprintf('command failed with message: %s', $exception->getMessage())
                ]
            ]);
        }

        return $this->delegateView([
            'contentTemplate' => 'SpeedleadBundle:Contact:import.html.php',
            'viewParameters' => [
                'reports' => $reports,
            ]
        ]);
    }
}
