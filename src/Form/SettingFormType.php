<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Form;

use Harmony\Bundle\SettingsManagerBundle\Form\Type\DomainType;
use Harmony\Bundle\SettingsManagerBundle\Model\Setting;
use Harmony\Bundle\SettingsManagerBundle\Model\Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('domain', DomainType::class)
            ->add('name', null, [
                'disabled' => true,
                'translation_domain' => 'HarmonySettingsManager',
                'label' => 'edit.form.name',
            ])
            ->add('type', ChoiceType::class, [
                'choices' => Type::toArray(),
                'disabled' => true,
                'translation_domain' => 'HarmonySettingsManager',
                'choice_translation_domain' => 'HarmonySettingsManager',
                'choice_label' => function (string $type) {
                    return 'type.' . strtolower($type);
                },
                'label' => 'edit.form.type',
            ])
            ->add('description', TextareaType::class, [
                'translation_domain' => 'HarmonySettingsManager',
                'label' => 'edit.form.description',
                'required' => false,
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var Setting $model */
            if (($model = $event->getData()) === null) {
                return;
            }

            $options = [
                'translation_domain' => 'HarmonySettingsManager',
                'label'              => 'edit.form.is_enabled'
            ];
            if ($model->getType()->equals(Type::BOOL()) || $model->getType()->getValue() === 'bool') {
                $options += ['required' => false];
            } elseif ($model->getType()->equals(Type::INT()) || $model->getType()->getValue() === 'int') {
                $options += ['scale' => 0];
            } elseif ($model->getType()->equals(Type::FLOAT()) || $model->getType()->getValue() === 'float') {
                $options += ['scale' => 2];
            } elseif ($model->getType()->equals(Type::YAML()) || $model->getType()->getValue() === 'yaml') {
                $options += ['attr' => ['rows' => 12]];
            } elseif ($model->getType()->equals(Type::CHOICE()) || $model->getType()->getValue() === 'choice') {
                $options += [
                    'placeholder' => 'edit.form.choice_placeholder',
                    'choices'     => array_values($model->getChoices()) === $model->getChoices() ?
                        array_combine($model->getChoices(), $model->getChoices()) : $model->getChoices()
                ];
            } else {
                $options += ['required' => false];
            }

            $event
                ->getForm()
                ->add('data', Type::getTypeName($model->getType()->getValue()),
                    array_merge($options, $model->getTypeOptions()));
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => Setting::class,
                'method' => 'POST',
            ]);
    }
}
