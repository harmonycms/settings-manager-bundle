<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Form\Type;

use Harmony\Bundle\SettingsManagerBundle\Model\DomainModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DomainType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, [
            'compound' => false,
            'disabled' => true,
            'translation_domain' => 'HarmonySettingsManager',
            'label' => 'edit.form.domain',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => DomainModel::class,
                'compound' => true,
                'label' => false,
            ]);
    }
}
