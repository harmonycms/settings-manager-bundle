<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Model;

/**
 * Class SettingDomain
 *
 * @package Harmony\Bundle\SettingsManagerBundle\Model
 */
abstract class SettingDomain implements SettingDomainInterface
{

    public const DEFAULT_NAME = 'default';

    /** @var string $name */
    protected $name;

    /** @var int $priority */
    protected $priority = 0;

    /** @var bool $enabled */
    protected $enabled = false;

    /** @var bool $readOnly */
    protected $readOnly = false;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): SettingDomain
    {
        $this->name = $name;

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): SettingDomain
    {
        $this->priority = $priority;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->name === self::DEFAULT_NAME ? true : $this->enabled;
    }

    public function setEnabled(bool $enabled): SettingDomain
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function isReadOnly(): bool
    {
        return $this->name === self::DEFAULT_NAME ? true : $this->readOnly;
    }

    public function setReadOnly(bool $readOnly): SettingDomain
    {
        $this->readOnly = $readOnly;

        return $this;
    }
}
