<?php
declare(strict_types = 1);

namespace MauticPlugin\SpeedleadBundle\Repository;

use Mautic\CoreBundle\Entity\CommonRepository;
use MauticPlugin\SpeedleadBundle\Entity\SurveyConfiguration;

class SurveyConfigurationRepository extends CommonRepository
{
    public function save(SurveyConfiguration $surveyConfiguration): void
    {
        $this->_em->persist($surveyConfiguration);
        $this->_em->flush($surveyConfiguration);
    }

    public function remove(SurveyConfiguration $surveyConfiguration): void
    {
        $this->_em->remove($surveyConfiguration);
        $this->_em->flush($surveyConfiguration);
    }
}
