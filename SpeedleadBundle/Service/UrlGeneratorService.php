<?php
declare(strict_types = 1);

namespace MauticPlugin\SpeedleadBundle\Service;

class UrlGeneratorService
{
    public function generateUrlReportFrontend(string $instance, int $reportId, string $fairId): string
    {
        return sprintf(
            '%s/#/fair/report/edit?report=%s&fair=%s',
            $instance,
            $reportId,
            $fairId
        );
    }
}
