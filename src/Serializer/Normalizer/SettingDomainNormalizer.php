<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Serializer\Normalizer;

use Harmony\Bundle\SettingsManagerBundle\Model\SettingDomain;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;

/**
 * Class SettingDomainNormalizer
 *
 * @package Harmony\Bundle\SettingsManagerBundle\Serializer\Normalizer
 */
class SettingDomainNormalizer implements NormalizerInterface, DenormalizerInterface
{

    use ObjectToPopulateTrait;

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
        isset($data['enabled']) && $object->setEnabled($data['enabled']);
        isset($data['read_only']) && $object->setReadOnly($data['read_only']);
        isset($data['priority']) && $object->setPriority($data['priority']);

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
        return is_a($type, SettingDomain::class, true);
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
            'name'      => $object->getName(),
            'enabled'   => $object->isEnabled(),
            'read_only' => $object->isReadOnly(),
            'priority'  => $object->getPriority(),
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
        return $data instanceof SettingDomain;
    }
}
