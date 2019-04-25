<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Model;

/**
 * Class SettingTag
 *
 * @package Harmony\Bundle\SettingsManagerBundle\Model
 */
class SettingTag implements SettingTagInterface
{

    /**
     * @var int $id
     */
    protected $id;

    /**
     * @var string $name
     */
    protected $name;

    /**
     * Set Id
     *
     * @param int $id
     *
     * @return SettingTag
     */
    public function setId(int $id): SettingTag
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set Name
     *
     * @param string $name
     *
     * @return SettingTag
     */
    public function setName(string $name): SettingTag
    {
        $this->name = $name;

        return $this;
    }
}
