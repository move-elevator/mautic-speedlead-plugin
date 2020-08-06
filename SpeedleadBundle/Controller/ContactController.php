<?php
declare(strict_types = 1);

namespace MauticPlugin\SpeedleadBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\PluginBundle\Entity\Integration;
use Symfony\Component\HttpFoundation\Response;

class ContactController extends AbstractFormController
{
    public function importAction(): Response
    {
        $translator = $this->get('translator');

        $integrationRepository = $this
            ->getDoctrine()
            ->getRepository(Integration::class);

        /** @var Integration $integration */
        $integration = $integrationRepository->findOneBy(['name' => 'Speedlead']);

        if (false === $integration instanceof Integration) {
            return $this->delegateView([
                'contentTemplate' => 'SpeedleadBundle:Contact:import.html.php',
                'viewParameters' => [
                    'message' => $translator->trans('mautic.speedlead.no_plugin_conf_found')
                ]
            ]);
        }

        if (true === empty($integration->getFeatureSettings())) {
            return $this->delegateView([
                'contentTemplate' => 'SpeedleadBundle:Contact:import.html.php',
                'viewParameters' => [
                    'message' => $translator->trans('mautic.speedlead.plugin_conf_found_but_no_feature_settings')
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
                    'message' => $translator->trans('mautic.speedlead.import_failed_with_msg', ['%message%' => $exception->getMessage()])
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
