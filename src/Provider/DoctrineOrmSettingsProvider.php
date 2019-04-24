<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Harmony\Bundle\SettingsManagerBundle\Exception\ReadOnlyProviderException;
use Harmony\Bundle\SettingsManagerBundle\Model\Setting;
use Harmony\Bundle\SettingsManagerBundle\Model\SettingDomain;
use Harmony\Bundle\SettingsManagerBundle\Model\SettingInterface;
use Harmony\Bundle\SettingsManagerBundle\Model\SettingTag;
use Harmony\Bundle\SettingsManagerBundle\Model\SettingTagInterface;
use Harmony\Bundle\SettingsManagerBundle\Provider\Traits\WritableProviderTrait;
use function array_diff;
use function array_map;
use function array_merge;
use function count;

/**
 * Class DoctrineOrmSettingsProvider
 *
 * @package Harmony\Bundle\SettingsManagerBundle\Provider
 */
class DoctrineOrmSettingsProvider implements SettingsProviderInterface
{

    use WritableProviderTrait;

    /** @var ManagerRegistry $registry */
    protected $registry;

    /** @var string $settingsEntityClass */
    protected $settingsEntityClass = '';

    /** @var string $tagEntityClass */
    protected $tagEntityClass = '';

    /**
     * DoctrineOrmSettingsProvider constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
        try {
            $this->settingsEntityClass = $registry->getManager()
                ->getClassMetadata(SettingInterface::class)
                ->getName();
            $this->tagEntityClass      = $registry->getManager()
                ->getClassMetadata(SettingTagInterface::class)
                ->getName();
        }
        catch (MappingException $mappingException) {
        }
    }

    /**
     * Collects all settings based on given domains.
     *
     * @param string[] $domainNames Domains names to check
     *
     * @return Setting[]
     */
    public function getSettings(array $domainNames): array
    {
        $qb = $this->registry->getRepository($this->settingsEntityClass)->createQueryBuilder();
        $qb->select('s')
            ->from($this->settingsEntityClass, 's')
            ->where($qb->expr()->in('s.domain.name', ':domainNames'))
            ->setParameter('domainNames', $domainNames)
            ->setMaxResults(300);

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns setting by name.
     *
     * @param string[] $domainNames  Domains names to check
     * @param string[] $settingNames Settings to check in those domains
     *
     * @return Setting[]
     */
    public function getSettingsByName(array $domainNames, array $settingNames): array
    {
        $qb = $this->registry->getRepository($this->settingsEntityClass)->createQueryBuilder();
        $qb->select('s')
            ->from($this->settingsEntityClass, 's')
            ->where($qb->expr()->andX($qb->expr()
                ->in('s.name', ':settingNames'), $qb->expr()->in('s.domain.name', ':domainNames')))
            ->setParameter('domainNames', $domainNames)
            ->setParameter('settingNames', $settingNames)
            ->setMaxResults(300);

        return $qb->getQuery()->getResult();
    }

    /**
     * Collects all domain models.
     *
     * @param bool $onlyEnabled
     *
     * @return SettingDomain[]
     */
    public function getDomains(bool $onlyEnabled = false): array
    {
        $qb = $this->registry->getRepository($this->settingsEntityClass)->createQueryBuilder();
        $qb->select('DISTINCT s.domain.name AS name')
            ->addSelect('s.domain.priority AS priority')
            ->addSelect('s.domain.enabled AS enabled')
            ->addSelect('s.domain.readOnly AS readOnly')
            ->from($this->settingsEntityClass, 's')
            ->setMaxResults(100);

        if ($onlyEnabled) {
            $qb->andWhere($qb->expr()->orX($qb->expr()->eq('s.domain.enabled', ':enabled'),
                $qb->expr()->eq('s.domain.name', ':default_name')))
                ->setParameter('enabled', true)
                ->setParameter('default_name', SettingDomain::DEFAULT_NAME);
        }

        return array_map(function ($row) {
            $model = new SettingDomain();
            $model->setName($row['name']);
            $model->setPriority($row['priority']);
            $model->setEnabled($row['enabled']);
            $model->setReadOnly($row['readOnly']);

            return $model;
        }, $qb->getQuery()->getArrayResult());
    }

    /**
     * Saves setting model.
     * Settings manager can still try to call this method even if it's read only.
     * In case make sure it throws ReadOnlyProviderException.
     *
     * @param Setting $settingModel
     *
     * @return bool Status of save process
     * @throws ReadOnlyProviderException When provider is read only
     */
    public function save(Setting $settingModel): bool
    {
        if ($this->registry->getRepository($this->settingsEntityClass)->contains($settingModel)) {
            $this->registry->getRepository($this->settingsEntityClass)->persist($settingModel);
            $this->registry->getRepository($this->settingsEntityClass)->flush();

            return true;
        }

        $entity = $this->registry->getRepository($this->settingsEntityClass)
            ->getRepository($this->settingsEntityClass)
            ->findOneBy([
                'name'        => $settingModel->getName(),
                'domain.name' => $settingModel->getDomain()->getName(),
            ]);

        if ($entity !== null) {
            $entity->setData($settingModel->getData());
        } else {
            $entity = $this->transformModelToEntity($settingModel);
        }

        $this->registry->getRepository($this->settingsEntityClass)->persist($entity);
        $this->registry->getRepository($this->settingsEntityClass)->flush();

        return true;
    }

    /**
     * Removes setting from provider.
     *
     * @param Setting $settingModel
     *
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function delete(Setting $settingModel): bool
    {
        $qb = $this->registry->getRepository($this->settingsEntityClass)->createQueryBuilder();
        $qb->delete($this->settingsEntityClass, 's')->where($qb->expr()->andX($qb->expr()->eq('s.name', ':sname'),
            $qb->expr()->eq('s.domain.name', ':dname')))->setParameters([
            'sname' => $settingModel->getName(),
            'dname' => $settingModel->getDomain()->getName(),
        ]);

        $success = ((int)$qb->getQuery()->getSingleScalarResult()) > 0;

        if ($success) {
            $this->registry->getRepository()->clear($this->settingsEntityClass);
        }

        return $success;
    }

    /**
     * Updates domain model in provider.
     *
     * @param SettingDomain $domainModel
     *
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function updateDomain(SettingDomain $domainModel): bool
    {
        $qb = $this->registry->getRepository($this->settingsEntityClass)->createQueryBuilder();
        $qb->update($this->settingsEntityClass, 's')
            ->set('s.domain.enabled', ':enabled')
            ->set('s.domain.priority', ':priority')
            ->where($qb->expr()->eq('s.domain.name', ':dname'))
            ->setParameter('enabled', $domainModel->isEnabled())
            ->setParameter('priority', $domainModel->getPriority())
            ->setParameter('dname', $domainModel->getName());

        $success = ((int)$qb->getQuery()->getSingleScalarResult()) > 0;

        if ($success) {
            $this->registry->getRepository($this->settingsEntityClass)->clear($this->settingsEntityClass);
        }

        return $success;
    }

    /**
     * Removes domain and all settings associated with it.
     *
     * @param string $domainName
     *
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function deleteDomain(string $domainName): bool
    {
        $qb = $this->registry->getRepository($this->settingsEntityClass)->createQueryBuilder();
        $qb->delete($this->settingsEntityClass, 's')
            ->where($qb->expr()->eq('s.domain.name', ':dname'))
            ->setParameter('dname', $domainName);

        $success = ((int)$qb->getQuery()->getSingleScalarResult()) > 0;

        if ($success) {
            $this->registry->getRepository($this->settingsEntityClass)->clear($this->settingsEntityClass);
        }

        return $success;
    }

    /**
     * @param Setting $model
     *
     * @return Setting
     */
    protected function transformModelToEntity(Setting $model): Setting
    {
        // transform setting

        if (!$model instanceof $this->settingsEntityClass) {
            /** @var Setting $entity */
            $entity = new $this->settingsEntityClass();
            $entity->setName($model->getName())
                ->setType($model->getType())
                ->setTypeOptions($model->getTypeOptions())
                ->setDescription($model->getDescription())
                ->setDataValue($model->getDataValue())
                ->setDomain($model->getDomain())
                ->setChoices($model->getChoices());

            $entity->setTags($model->getTags());
            $model = $entity;
        }

        // transform tags

        if ($this->tagEntityClass && $model->getTags()->count() > 0) {
            $knownTags       = [];
            $tagNamesToFetch = [];

            foreach ($model->getTags() as $tag) {
                if ($this->registry->getRepository($this->tagEntityClass)->contains($tag)) {
                    $knownTags[] = $tag;
                } else {
                    $tagNamesToFetch[] = $tag->getName();
                }
            }

            if (count($tagNamesToFetch) > 0) {
                /** @var SettingTag[] $fetchedTags */
                $fetchedTags = $this->registry->getRepository($this->tagEntityClass)
                    ->getRepository($this->tagEntityClass)
                    ->findBy(['name' => $tagNamesToFetch]);

                if (count($fetchedTags) !== count($tagNamesToFetch)) {
                    $fetchedTagNames = [];
                    foreach ($fetchedTags as $fetchedTag) {
                        $fetchedTagNames[] = $fetchedTag->getName();
                    }

                    foreach (array_diff($tagNamesToFetch, $fetchedTagNames) as $newTagName) {
                        /** @var SettingTag $newTag */
                        $newTag = new $this->tagEntityClass();
                        $newTag->setName($newTagName);
                        $fetchedTags[] = $newTag;
                    }
                }

                $knownTags = array_merge($knownTags, $fetchedTags);
            }

            $model->setTags(new ArrayCollection($knownTags));
        }

        return $model;
    }
}
