<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueSetting extends Constraint
{
    public $message = '{{ domainName }} domain already has setting named {{ settingName }}';

    public function getTargets()
    {
        return Constraint::CLASS_CONSTRAINT;
    }
}
