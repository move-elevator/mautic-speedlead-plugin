<?php
declare(strict_types = 1);

namespace MauticPlugin\SpeedleadBundle\Service;

use MauticPlugin\SpeedleadBundle\Entity\SurveyConfiguration;
use Symfony\Component\Form\FormInterface;

class SurveyConfigurationMapperService
{
    public function mapFormToSurveyConfiguration(
        FormInterface $form,
        SurveyConfiguration $surveyConfiguration
    ): SurveyConfiguration {
        $surveyConfigurationArray = json_decode($surveyConfiguration->getConfiguration(), true);

        foreach ($surveyConfigurationArray as $fieldKey => $field) {
            $surveyConfigurationArray[$fieldKey]['import'] = $form->getData()[$fieldKey];

            foreach ($field['options'] as $optionKey => $option) {
                if (false === $surveyConfigurationArray[$fieldKey]['import']) {
                    continue;
                }

                $surveyConfigurationArray[$fieldKey]['options'][$optionKey]['tag'] = $form->getData()[sprintf(
                    '%s_%s',
                    $fieldKey,
                    $optionKey
                )];
            }
        }

        $surveyConfiguration->setConfiguration(json_encode($surveyConfigurationArray));

        return $surveyConfiguration;
    }
}
