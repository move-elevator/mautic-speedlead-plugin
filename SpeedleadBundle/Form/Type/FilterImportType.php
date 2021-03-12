<?php
declare(strict_types = 1);

namespace MauticPlugin\SpeedleadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilterImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('createdBefore', TextType::class, [
                'required' => true,
                'label' => 'mautic.speedlead.filter.created_before',
                'data' => 'now'
            ])
            ->add('updatedAfter', TextType::class, [
                'required' => true,
                'label' => 'mautic.speedlead.filter.updated_after',
                'data' => '-4hours'
            ]);

        foreach ($options['surveyConfiguration'] as $fieldKey => $field) {
            $builder->add($fieldKey, CheckboxType::class, [
                'required' => false,
                'data' => $field['import'],
                'label' => $field['label']
            ]);

            foreach ($field['options'] as $optionKey => $option) {
                $builder->add(sprintf('%s_%s', $fieldKey, $optionKey), TextType::class, [
                    'required' => false,
                    'data' => $option['tag'],
                    'label' => $option['label']
                ]);
            }
        }

        $builder->add('submit', SubmitType::class, [
            'label' => 'mautic.speedlead.contact.import'
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'surveyConfiguration' => null
            ]);
    }

    public function getBlockPrefix(): string
    {
        return 'speedlead_bundle_filter_import_type';
    }
}
