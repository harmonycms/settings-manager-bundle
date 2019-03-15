<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Serializer\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Harmony\Bundle\SettingsManagerBundle\Model\DomainModel;
use Harmony\Bundle\SettingsManagerBundle\Model\SettingModel;
use Harmony\Bundle\SettingsManagerBundle\Model\TagModel;
use Harmony\Bundle\SettingsManagerBundle\Model\Type;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

class SettingModelNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait, ObjectToPopulateTrait;

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $object = $this->extractObjectToPopulate($class, $context) ?? new $class();

        isset($data['name']) && $object->setName($data['name']);
        isset($data['description']) && $object->setDescription($data['description']);
        isset($data['domain']) && $object->setDomain(
            $this->serializer->denormalize($data['domain'], DomainModel::class, $format, $context)
        );
        isset($data['type']) && $object->setType(new Type($data['type']));
        isset($data['type_options']) && $object->setTypeOptions($data['type_options']);
        isset($data['data']) && $object->setDataValue($data['data']);
        isset($data['tags']) && $object->setTags(new ArrayCollection(
            $this->serializer->denormalize($data['tags'], TagModel::class . '[]', $format, $context)
        ));
        isset($data['choices']) && $object->setChoices($data['choices']);

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_a($type, SettingModel::class, true);
    }

    /**
     * {@inheritdoc}
     *
     * @param SettingModel $object
     */
    public function normalize($object, $format = null, array $context = [])
    {
        return [
            'name' => $object->getName(),
            'description' => $object->getDescription(),
            'domain' => $this->serializer->normalize($object->getDomain(), $format, $context),
            'type' => $object->getType()->getValue(),
            'type_options' => $object->getTypeOptions(),
            'data' => $object->getDataValue(),
            'tags' => $this->serializer->normalize($object->getTags(), $format, $context),
            'choices' => $object->getChoices(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof SettingModel;
    }
}
