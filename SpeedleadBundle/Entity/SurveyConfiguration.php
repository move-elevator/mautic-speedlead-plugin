<?php
declare(strict_types = 1);

namespace MauticPlugin\SpeedleadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CommonEntity;
use MauticPlugin\SpeedleadBundle\Repository\SurveyConfigurationRepository;

class SurveyConfiguration extends CommonEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $fairId;

    /**
     * @var string
     */
    private $configuration;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder
            ->setTable('speedlead_survey_configuration')
            ->setCustomRepositoryClass(SurveyConfigurationRepository::class);

        $builder->addId();
        $builder
            ->createField('fairId', 'string')
            ->columnName('fair_id')
            ->nullable(false)
            ->build();
        $builder
            ->createField('configuration', 'text')
            ->columnName('configuration')
            ->nullable(false)
            ->build();
    }

    public static function createFromApiResponse(array $survey): SurveyConfiguration
    {
        $importFormTypes = [
            'checkbox',
            'checkbox_group',
            'advanced_checkbox_group',
            'radio_button',
            'radio_button_group',
            'advanced_radio_button_group',
            'select',
            'multi_select'
        ];

        $surveyConfiguration = new self();
        $surveyConfiguration->setFairId($survey['fair']);

        $configuration = [];

        foreach ($survey['fields'] as $field) {
            if (false === in_array($field['form_type'], $importFormTypes, true)) {
                continue;
            }

            $configuration[$field['field_key']] = [
                'label' => $field['label'],
                'formType' => $field['form_type'],
                'import' => false,
                'options' => []
            ];

            foreach ($field['options'] as $option) {
                if ('other' === $option) {
                    continue;
                }

                $configuration[$field['field_key']]['options'][] = [
                    'label' => $option,
                    'tag' => null
                ];
            }
        }

        $surveyConfiguration->setConfiguration(json_encode($configuration));

        return $surveyConfiguration;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getFairId(): string
    {
        return $this->fairId;
    }

    public function setFairId(string $fairId): void
    {
        $this->fairId = $fairId;
    }

    public function getConfiguration(): string
    {
        return $this->configuration;
    }

    public function setConfiguration(string $configuration): void
    {
        $this->configuration = $configuration;
    }
}
