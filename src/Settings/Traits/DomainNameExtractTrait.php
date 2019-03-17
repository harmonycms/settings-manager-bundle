<?php
declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Settings\Traits;

use Harmony\Bundle\SettingsManagerBundle\Model\SettingDomain;

trait DomainNameExtractTrait
{
    /**
     * @param SettingDomain[] $domainModels
     *
     * @return string[]
     */
    protected function extractDomainNames(array $domainModels): array
    {
        return array_map(function (SettingDomain $model) {
            return $model->getName();
        }, $domainModels);
    }
}
