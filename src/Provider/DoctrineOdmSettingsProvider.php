<?php

namespace Harmony\Bundle\SettingsManagerBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ManagerRegistry;
use Harmony\Bundle\SettingsManagerBundle\Exception\ReadOnlyProviderException;
use Harmony\Bundle\SettingsManagerBundle\Model\DomainModel;
use Harmony\Bundle\SettingsManagerBundle\Model\SettingModel;
use Harmony\Bundle\SettingsManagerBundle\Model\TagModel;
use Harmony\Bundle\SettingsManagerBundle\Provider\Traits\WritableProviderTrait;

/**
 * Class DoctrineOdmSettingsProvider
 *
 * @package Harmony\Bundle\SettingsManagerBundle\Provider
 */
class DoctrineOdmSettingsProvider implements SettingsProviderInterface
{

    use WritableProviderTrait;

    /** @var ManagerRegistry $registry */
    protected $registry;

    /** @var string $settingsDocumentClass */
    protected $settingsDocumentClass;

    /** @var string $tagDocumentClass */
    protected $tagDocumentClass;

    /**
     * DoctrineOdmSettingsProvider constructor.
     *
     * @param ManagerRegistry $registry
     * @param string          $settingsDocumentClass
     * @param string|null     $tagDocumentClass
     */
    public function __construct(ManagerRegistry $registry, string $settingsDocumentClass,
                                string $tagDocumentClass = null)
    {
        $this->registry = $registry;

        if (!is_subclass_of($settingsDocumentClass, SettingModel::class)) {
            throw new \UnexpectedValueException($settingsDocumentClass . ' is not part of the model ' .
                SettingModel::class);
        }

        $this->settingsDocumentClass = $settingsDocumentClass;

        if ($tagDocumentClass !== null) {
            if (!is_subclass_of($tagDocumentClass, TagModel::class)) {
                throw new \UnexpectedValueException($tagDocumentClass . ' is not part of the model ' . TagModel::class);
            }

            $this->tagDocumentClass = $tagDocumentClass;
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
        $criteria = new Criteria();
        $criteria->expr()->in('domain.name', $domainNames);

        return $this->registry->getRepository($this->settingsDocumentClass)->matching($criteria)->toArray();
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
        $criteria = new Criteria();
        $criteria->expr()->andX($criteria->expr()->in('name', $settingNames),
            $criteria->expr()->in('domain.name', $domainNames));

        return $this->registry->getRepository($this->settingsDocumentClass)->matching($criteria)->toArray();
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
        if ($this->registry->getManager()->contains($settingModel)) {
            $this->registry->getManager()->persist($settingModel);
            $this->registry->getManager()->flush();

            return true;
        }

        $document = $this->registry->getRepository($this->settingsDocumentClass)->findOneBy([
            'name'        => $settingModel->getName(),
            'domain.name' => $settingModel->getDomain()->getName(),
        ]);

        if ($document !== null) {
            $document->setData($settingModel->getData());
        } else {
            $document = $this->transformModelToDocument($settingModel);
        }

        $this->registry->getManager()->persist($document);
        $this->registry->getManager()->flush();

        return true;
    }

    /**
     * Removes setting from provider.
     *
     * @param SettingModel $settingModel
     *
     * @return bool
     */
    public function delete(SettingModel $settingModel): bool
    {
        return false;
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
        return [];
    }

    /**
     * Updates domain model in provider.
     *
     * @param DomainModel $domainModel
     *
     * @return bool
     */
    public function updateDomain(DomainModel $domainModel): bool
    {
        return false;
    }

    /**
     * Removes domain and all settings associated with it.
     *
     * @param string $domainName
     *
     * @return bool
     */
    public function deleteDomain(string $domainName): bool
    {
        return false;
    }

    /**
     * @param SettingModel $model
     *
     * @return SettingModel
     */
    protected function transformModelToDocument(SettingModel $model): SettingModel
    {

        // transform setting

        if (!$model instanceof $this->settingsDocumentClass) {
            /** @var SettingModel $document */
            $document = new $this->settingsDocumentClass();
            $document->setName($model->getName())
                ->setType($model->getType())
                ->setTypeOptions($model->getTypeOptions())
                ->setDescription($model->getDescription())
                ->setDataValue($model->getDataValue())
                ->setDomain($model->getDomain())
                ->setChoices($model->getChoices());

            $document->setTags($model->getTags());
            $model = $document;
        }

        // transform tags

        if ($this->tagDocumentClass && $model->getTags()->count() > 0) {
            $knownTags       = [];
            $tagNamesToFetch = [];

            foreach ($model->getTags() as $tag) {
                if ($this->registry->getManager()->contains($tag)) {
                    $knownTags[] = $tag;
                } else {
                    $tagNamesToFetch[] = $tag->getName();
                }
            }

            if (count($tagNamesToFetch) > 0) {
                /** @var TagModel[] $fetchedTags */
                $fetchedTags = $this->registry->getRepository($this->tagDocumentClass)
                    ->findBy(['name' => $tagNamesToFetch]);

                if (count($fetchedTags) !== count($tagNamesToFetch)) {
                    $fetchedTagNames = [];
                    foreach ($fetchedTags as $fetchedTag) {
                        $fetchedTagNames[] = $fetchedTag->getName();
                    }

                    foreach (array_diff($tagNamesToFetch, $fetchedTagNames) as $newTagName) {
                        /** @var TagModel $newTag */
                        $newTag = new $this->tagDocumentClass();
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