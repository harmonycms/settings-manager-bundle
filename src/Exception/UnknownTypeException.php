<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Exception;

class UnknownTypeException extends \Exception implements SettingsException
{
    public function __construct(string $type)
    {
        parent::__construct('Cannot handle setting of type ' . $type);
    }
}
