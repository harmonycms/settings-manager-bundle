<?php
declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Validator\Constraints;

use Harmony\Bundle\SettingsManagerBundle\Model\Setting;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintValidator;

class SettingTypeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof Setting || !$constraint instanceof SettingType) {
            return;
        }

        $type = $value->getType();
        if ($type === null || $value->getData() === null) {
            return;
        }

        $type = $type->getValue();
        $type = $type === 'yaml' ? 'array' : $type;
        $type = $type === 'choice' ? 'string' : $type;

        $this
            ->context
            ->getValidator()
            ->inContext($this->context)
            ->validate($value->getData(), new Type(['type' => $type, 'message' => $constraint->message]));
    }
}
