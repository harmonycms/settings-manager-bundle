<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Model;

class TagModel
{
    private $name;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): TagModel
    {
        $this->name = $name;

        return $this;
    }
}
