<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Harmony\Bundle\SettingsManagerBundle\Exception\ReadOnlyProviderException;
use Harmony\Bundle\SettingsManagerBundle\Model\DomainModel;
use Harmony\Bundle\SettingsManagerBundle\Model\SettingModel;
use Harmony\Bundle\SettingsManagerBundle\Model\TagModel;
use Harmony\Bundle\SettingsManagerBundle\Provider\Traits\WritableProviderTrait;

/**
 * Class DoctrineOrmSettingsProvider
 *
 * @package Harmony\Bundle\SettingsManagerBundle\Provider
 */
class DoctrineOrmSettingsProvider implements SettingsProviderInterface
{

    use WritableProviderTrait;

    /** @var EntityManagerInterface $entityManager */
    protected $entityManager;

    /** @var string $settingsEntityClass */
    protected $settingsEntityClass;

    /** @var string $tagEntityClass */
    protected $tagEntityClass;

    /**
     * DoctrineOrmSettingsProvider constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param string                 $settingsEntityClass
     * @param string|null            $tagEntityClass
     */
    public function __construct(EntityManagerInterface $entityManager, string $settingsEntityClass,
                                string $tagEntityClass = null)
    {
        $this->entityManager = $entityManager;

        if (!is_subclass_of($settingsEntityClass, SettingModel::class)) {
            throw new \UnexpectedValueException($settingsEntityClass . ' is not part of the model ' .
                SettingModel::class);
        }

        $this->settingsEntityClass = $settingsEntityClass;

        if ($tagEntityClass !== null) {
            if (!is_subclass_of($tagEntityClass, TagModel::class)) {
                throw new \UnexpectedValueException($tagEntityClass . ' is not part of the model ' . TagModel::class);
            }

            $this->tagEntityClass = $tagEntityClass;
        }
    }

    /**
     * Collects all settings based on given domains.
     *
     * @param string[] $domainNames Domains names to check
     *
     * @return SettingModel[]
     */
    public function getSettings(array $domainNames): array
    {
        $qb = $this->entityManager->createQueryBuilder();
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
     * @return SettingModel[]
     */
    public function getSettingsByName(array $domainNames, array $settingNames): array
    {
        $qb = $this->entityManager->createQueryBuilder();
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
     * @return DomainModel[]
     */
    public function getDomains(bool $onlyEnabled = false): array
    {
        $qb = $this->entityManager->createQueryBuilder();
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
                ->setParameter('default_name', DomainModel::DEFAULT_NAME);
        }

        return array_map(function ($row) {
            $model = new DomainModel();
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
     * @param SettingModel $settingModel
     *
     * @return bool Status of save process
     * @throws ReadOnlyProviderException When provider is read only
     */
    public function save(SettingModel $settingModel): bool
    {
        if ($this->entityManager->contains($settingModel)) {
            $this->entityManager->persist($settingModel);
            $this->entityManager->flush();

            return true;
        }

        $entity = $this->entityManager->getRepository($this->settingsEntityClass)->findOneBy([
            'name'        => $settingModel->getName(),
            'domain.name' => $settingModel->getDomain()->getName(),
        ]);

        if ($entity !== null) {
            $entity->setData($settingModel->getData());
        } else {
            $entity = $this->transformModelToEntity($settingModel);
        }

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Removes setting from provider.
     *
     * @param SettingModel $settingModel
     *
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function delete(SettingModel $settingModel): bool
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete($this->settingsEntityClass, 's')->where($qb->expr()->andX($qb->expr()->eq('s.name', ':sname'),
            $qb->expr()->eq('s.domain.name', ':dname')))->setParameters([
            'sname' => $settingModel->getName(),
            'dname' => $settingModel->getDomain()->getName(),
        ]);

        $success = ((int)$qb->getQuery()->getSingleScalarResult()) > 0;

        if ($success) {
            $this->entityManager->clear($this->settingsEntityClass);
        }

        return $success;
    }

    /**
     * Updates domain model in provider.
     *
     * @param DomainModel $domainModel
     *
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function updateDomain(DomainModel $domainModel): bool
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->update($this->settingsEntityClass, 's')
            ->set('s.domain.enabled', ':enabled')
            ->set('s.domain.priority', ':priority')
            ->where($qb->expr()->eq('s.domain.name', ':dname'))
            ->setParameter('enabled', $domainModel->isEnabled())
            ->setParameter('priority', $domainModel->getPriority())
            ->setParameter('dname', $domainModel->getName());

        $success = ((int)$qb->getQuery()->getSingleScalarResult()) > 0;

        if ($success) {
            $this->entityManager->clear($this->settingsEntityClass);
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
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete($this->settingsEntityClass, 's')
            ->where($qb->expr()->eq('s.domain.name', ':dname'))
            ->setParameter('dname', $domainName);

        $success = ((int)$qb->getQuery()->getSingleScalarResult()) > 0;

        if ($success) {
            $this->entityManager->clear($this->settingsEntityClass);
        }

        return $success;
    }

    /**
     * @param SettingModel $model
     *
     * @return SettingModel
     */
    protected function transformModelToEntity(SettingModel $model): SettingModel
    {
        // transform setting

        if (!$model instanceof $this->settingsEntityClass) {
            /** @var SettingModel $entity */
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
                if ($this->entityManager->contains($tag)) {
                    $knownTags[] = $tag;
                } else {
                    $tagNamesToFetch[] = $tag->getName();
                }
            }

            if (count($tagNamesToFetch) > 0) {
                /** @var TagModel[] $fetchedTags */
                $fetchedTags = $this->entityManager->getRepository($this->tagEntityClass)
                    ->findBy(['name' => $tagNamesToFetch]);

                if (count($fetchedTags) !== count($tagNamesToFetch)) {
                    $fetchedTagNames = [];
                    foreach ($fetchedTags as $fetchedTag) {
                        $fetchedTagNames[] = $fetchedTag->getName();
                    }

                    foreach (array_diff($tagNamesToFetch, $fetchedTagNames) as $newTagName) {
                        /** @var TagModel $newTag */
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
