<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Harmony\Bundle\SettingsManagerBundle\Model\Setting;

/**
 * Class Setting
 *
 * @ORM\Entity()
 * @ORM\Table(name="settings_test_setting")
 */
class Setting extends Setting
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var ArrayCollection|SettingTag[]
     *
     * @ORM\ManyToMany(targetEntity="Tag", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinTable(name="settings_test_setting__tag",
     *      joinColumns={@ORM\JoinColumn(name="setting_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="tag_id", referencedColumnName="id")}
     * )
     */
    protected $tags;

    /**
     * Setting constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
