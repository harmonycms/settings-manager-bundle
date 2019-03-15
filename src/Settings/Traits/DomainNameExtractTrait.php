<?php
declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Settings\Traits;

use Harmony\Bundle\SettingsManagerBundle\Model\DomainModel;

trait DomainNameExtractTrait
{
    /**
     * @param DomainModel[] $domainModels
     * @return string[]
     */
    protected function extractDomainNames(array $domainModels): array
    {
        return array_map(function (DomainModel $model) {
            return $model->getName();
        }, $domainModels);
    }
}
