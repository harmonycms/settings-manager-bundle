<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Class Setting
 *
 * @package Harmony\Bundle\SettingsManagerBundle\Model
 */
class Setting
{

    /** @var string $name */
    protected $name;

    /** @var string $description */
    protected $description;

    /** @var string $domain */
    protected $domain;

    /** @var ArrayCollection $tags */
    protected $tags;

    /** @var Type $type */
    protected $type;

    /** @var array $typeOptions */
    protected $typeOptions = [];

    /** @var array $data */
    protected $data = [];

    /** @var string $providerName */
    protected $providerName;

    /** @var array $choices */
    protected $choices = [];

    /**
     * Setting constructor.
     */
    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): Setting
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): Setting
    {
        $this->description = $description;

        return $this;
    }

    public function getDomain(): ?SettingDomain
    {
        return $this->domain;
    }

    public function setDomain(SettingDomain $domain): Setting
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * @return SettingTag[]|Collection
     */
    public function getTags(): Collection
    {
        return $this->tags ?? new ArrayCollection();
    }

    /**
     * @param SettingTag[]|Collection $tags
     *
     * @return Setting
     */
    public function setTags(Collection $tags): Setting
    {
        $this->tags = $tags;

        return $this;
    }

    public function addTag(SettingTag $tag): Setting
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function hasTag(string $string): bool
    {
        foreach ($this->tags as $tag) {
            if ($tag->getName() === $string) {
                return true;
            }
        }

        return false;
    }

    public function getType(): ?Type
    {
        return $this->type;
    }

    public function setType(Type $type): Setting
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return array
     */
    public function getTypeOptions(): array
    {
        return $this->typeOptions;
    }

    /**
     * @param array $typeOptions
     *
     * @return Setting
     */
    public function setTypeOptions(array $typeOptions): Setting
    {
        $this->typeOptions = $typeOptions;

        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getData()
    {
        return isset($this->data['value']) ? $this->data['value'] : null;
    }

    public function setData($data): Setting
    {
        $this->data['value'] = $data;

        return $this;
    }

    /**
     * @internal Used by serializer
     */
    public function getDataValue(): array
    {
        return $this->data ?? [];
    }

    /**
     * @internal Used by serializer
     */
    public function setDataValue(array $data): Setting
    {
        $this->data = $data;

        return $this;
    }

    public function getProviderName(): ?string
    {
        return $this->providerName;
    }

    public function setProviderName(string $providerName): Setting
    {
        $this->providerName = $providerName;

        return $this;
    }

    /**
     * @return array
     */
    public function getChoices(): array
    {
        return $this->choices;
    }

    /**
     * @param array $choices
     *
     * @return Setting
     */
    public function setChoices(array $choices): Setting
    {
        $this->choices = $choices;

        return $this;
    }
}
