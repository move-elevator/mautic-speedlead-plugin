<?php
declare(strict_types = 1);

namespace MauticPlugin\SpeedleadBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\SpeedleadBundle\Entity\SurveyConfiguration;
use MauticPlugin\SpeedleadBundle\Form\Type\FilterImportType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContactController extends AbstractFormController
{
    public function importAction(Request $request): Response
    {
        $translator = $this->get('translator');
        $encryptionHelper = $this->get('mautic.helper.encryption');
        $reportContactMapper = $this->get('mautic.speedlead.service.report_contact_mapper');

        $integrationRepository = $this
            ->getDoctrine()
            ->getRepository(Integration::class);
        $surveyConfigurationRepository = $this
            ->getDoctrine()
            ->getRepository(SurveyConfiguration::class);

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

        $survey = $this->get('mautic.speedlead.service.survey_api')->callApiGetSurvey();

        $surveyConfiguration = $surveyConfigurationRepository->findOneBy([
            'fairId' => $encryptionHelper->decrypt($integration->getApiKeys()['fair'])
        ]);

        if (false === $surveyConfiguration instanceof SurveyConfiguration) {
            $surveyConfiguration = SurveyConfiguration::createFromApiResponse($survey);
            $surveyConfigurationRepository->save($surveyConfiguration);
        }

        $form = $this
            ->createForm(FilterImportType::class, null, ['surveyConfiguration' => json_decode($surveyConfiguration->getConfiguration(), true)])
            ->handleRequest($request);

        if (true === $form->isSubmitted() && true === $form->isValid()) {
            try {
                // map survey data to entity and save
                $surveyConfigurationMapper = $this->get('mautic.speedlead.service.survey_configuration_mapper');
                $surveyConfiguration = $surveyConfigurationMapper->mapFormToSurveyConfiguration($form, $surveyConfiguration);
                $surveyConfigurationRepository->save($surveyConfiguration);

                //get reports
                $reports = $this->get('mautic.speedlead.service.report_api')->callApiGetReports(
                    $form->get('createdBefore')->getViewData(),
                    $form->get('updatedAfter')->getViewData()
                );

                // map reports
                foreach($reports as $report) {
                    $reportContactMapper->createContact($report, $integration->getFeatureSettings(), $surveyConfiguration);
                }
            } catch (\Throwable $exception) {
                return $this->delegateView([
                    'contentTemplate' => 'SpeedleadBundle:Contact:import.html.php',
                    'viewParameters' => [
                        'message' => $translator->trans('mautic.speedlead.import_failed_with_msg', ['%message%' =>
                            sprintf('%s in %s on line: %s', $exception->getMessage(), $exception->getFile(), $exception->getLine())
                        ])
                    ]
                ]);
            }

            return $this->delegateView([
                'contentTemplate' => 'SpeedleadBundle:Contact:import.html.php',
                'viewParameters' => [
                    'reports' => $reports,
                    'form' => $form->createView()
                ]
            ]);
        }

        return $this->delegateView([
            'contentTemplate' => 'SpeedleadBundle:Contact:import.html.php',
            'viewParameters' => [
                'form' => $form->createView(),
                'isAutomaticImportEnabled' => (bool) $integration->getFeatureSettings()['automaticImport']
            ]
        ]);
    }

    public function updateSurveyConfigurationAction(Request $request): Response
    {
        $integrationRepository = $this
            ->getDoctrine()
            ->getRepository(Integration::class);
        $surveyConfigurationRepository = $this
            ->getDoctrine()
            ->getRepository(SurveyConfiguration::class);

        /** @var Integration $integration */
        $integration = $integrationRepository->findOneBy(['name' => 'Speedlead']);

        $encryptionHelper = $this->get('mautic.helper.encryption');

        $survey = $this->get('mautic.speedlead.service.survey_api')->callApiGetSurvey();

        $surveyConfiguration = $surveyConfigurationRepository->findOneBy([
            'fairId' => $encryptionHelper->decrypt($integration->getApiKeys()['fair'])
        ]);

        if (true === $surveyConfiguration instanceof SurveyConfiguration) {
            $surveyConfigurationRepository->remove($surveyConfiguration);
        }

        $surveyConfiguration = SurveyConfiguration::createFromApiResponse($survey);
        $surveyConfigurationRepository->save($surveyConfiguration);

        return $this->redirectToRoute('mautic_speedlead_contact_import');
    }
}
