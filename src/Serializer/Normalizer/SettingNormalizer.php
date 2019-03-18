<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Serializer\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Harmony\Bundle\SettingsManagerBundle\Model\SettingDomain;
use Harmony\Bundle\SettingsManagerBundle\Model\Setting;
use Harmony\Bundle\SettingsManagerBundle\Model\SettingTag;
use Harmony\Bundle\SettingsManagerBundle\Model\Type;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

/**
 * Class SettingNormalizer
 *
 * @package Harmony\Bundle\SettingsManagerBundle\Serializer\Normalizer
 */
class SettingNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{

    use SerializerAwareTrait, ObjectToPopulateTrait;

    /**
     * Denormalizes data back into an object of the given class.
     *
     * @param mixed  $data    Data to restore
     * @param string $class   The expected class to instantiate
     * @param string $format  Format the given data was extracted from
     * @param array  $context Options available to the denormalizer
     *
     * @return object
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $object = $this->extractObjectToPopulate($class, $context) ?? new $class();

        isset($data['name']) && $object->setName($data['name']);
        isset($data['description']) && $object->setDescription($data['description']);
        isset($data['domain']) &&
        $object->setDomain($this->serializer->denormalize($data['domain'], SettingDomain::class, $format, $context));
        isset($data['type']) && $object->setType(new Type($data['type']));
        isset($data['type_options']) && $object->setTypeOptions($data['type_options']);
        isset($data['data']) && $object->setDataValue($data['data']);
        isset($data['tags']) &&
        $object->setTags(new ArrayCollection($this->serializer->denormalize($data['tags'], SettingTag::class . '[]',
            $format, $context)));
        isset($data['choices']) && $object->setChoices($data['choices']);

        return $object;
    }

    /**
     * Checks whether the given class is supported for denormalization by this normalizer.
     *
     * @param mixed  $data   Data to denormalize from
     * @param string $type   The class to which the data should be denormalized
     * @param string $format The format being deserialized from
     *
     * @return bool
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_a($type, Setting::class, true);
    }

    /**
     * Normalizes an object into a set of arrays/scalars.
     *
     * @param mixed  $object  Object to normalize
     * @param string $format  Format the normalization result will be encoded as
     * @param array  $context Context options for the normalizer
     *
     * @return array|string|int|float|bool
     */
    public function normalize($object, $format = null, array $context = [])
    {
        return [
            'name'         => $object->getName(),
            'description'  => $object->getDescription(),
            'domain'       => $this->serializer->normalize($object->getDomain(), $format, $context),
            'type'         => $object->getType()->getValue(),
            'type_options' => $object->getTypeOptions(),
            'data'         => $object->getDataValue(),
            'tags'         => $this->serializer->normalize($object->getTags(), $format, $context),
            'choices'      => $object->getChoices(),
        ];
    }

    /**
     * Checks whether the given class is supported for normalization by this normalizer.
     *
     * @param mixed  $data   Data to normalize
     * @param string $format The format being (de-)serialized from or into
     *
     * @return bool
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Setting;
    }
}
