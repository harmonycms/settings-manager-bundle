<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Serializer\Normalizer;

use Harmony\Bundle\SettingsManagerBundle\Model\SettingTag;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

class TagModelNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait, ObjectToPopulateTrait;

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $object = $this->extractObjectToPopulate($class, $context) ?? new $class();

        isset($data['name']) && $object->setName($data['name']);

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_a($type, SettingTag::class, true);
    }

    /**
     * {@inheritdoc}
     * @param SettingTag $object
     */
    public function normalize($object, $format = null, array $context = [])
    {
        return [
            'name' => $object->getName(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof SettingTag;
    }
}
