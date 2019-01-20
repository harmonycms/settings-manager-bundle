<?php

declare(strict_types=1);

namespace Helis\SettingsManagerBundle\Model;

use Helis\SettingsManagerBundle\Form\Type\YamlType;
use MyCLabs\Enum\Enum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

/**
 * Enum Type.
 * @method static Type STRING()
 * @method static Type BOOL()
 * @method static Type INT()
 * @method static Type FLOAT()
 * @method static Type YAML()
 * @method static Type CHOICE()
 */
class Type extends Enum
{

    public const BIRTHDAY           = BirthdayType::class;
    public const BOOL               = CheckboxType::class;
    public const BUTTON             = ButtonType::class;
    public const CHECKBOX           = CheckboxType::class;
    public const CHOICE             = ChoiceType::class;
    public const COLLECTION         = CollectionType::class;
    public const COLOR              = ColorType::class;
    public const COUNTRY            = CountryType::class;
    public const CURRENCY           = CurrencyType::class;
    public const DATETIME           = DateTimeType::class;
    public const DATETIME_IMMUTABLE = DateTimeType::class;
    public const DATE               = DateType::class;
    public const DATE_IMMUTABLE     = DateType::class;
    public const DATE_INTERVAL      = DateIntervalType::class;
    public const EMAIL              = EmailType::class;
    public const ENTITY             = EntityType::class;
    public const FILE               = FileType::class;
    public const FLOAT              = NumberType::class;
    public const FORM               = FormType::class;
    public const HIDDEN             = HiddenType::class;
    public const INT                = IntegerType::class;
    public const INTEGER            = IntegerType::class;
    public const LANGUAGE           = LanguageType::class;
    public const LOCALE             = LocaleType::class;
    public const MONEY              = MoneyType::class;
    public const NUMBER             = NumberType::class;
    public const PASSWORD           = PasswordType::class;
    public const PERCENT            = PercentType::class;
    public const RADIO              = RadioType::class;
    public const RANGE              = RangeType::class;
    public const REPEATED           = RepeatedType::class;
    public const RESET              = ResetType::class;
    public const SEARCH             = SearchType::class;
    public const STRING             = TextType::class;
    public const SUBMIT             = SubmitType::class;
    public const TEL                = TelType::class;
    public const TEXTAREA           = TextareaType::class;
    public const TEXT               = TextType::class;
    public const TIME               = TimeType::class;
    public const TIME_IMMUTABLE     = TimeType::class;
    public const TIMEZONE           = TimezoneType::class;
    public const URL                = UrlType::class;
    public const YAML               = YamlType::class;

    /**
     * Returns all possible values as an array
     *
     * @return array Constant name in key, constant value in value
     * @throws \ReflectionException
     */
    public static function toArray()
    {
        $class = \get_called_class();
        if (!isset(static::$cache[$class])) {
            $reflection            = new \ReflectionClass($class);
            static::$cache[$class] = $reflection->getConstants();
        }

        return array_unique(array_merge(array_keys(array_change_key_case(static::$cache[$class], CASE_LOWER)),
            array_values(static::$cache[$class])));
    }

    /**
     * Returns a value when called statically like so: MyEnum::SOME_VALUE() given SOME_VALUE is a class constant
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return static
     * @throws \BadMethodCallException
     */
    public static function __callStatic($name, $arguments)
    {
        $class = \get_called_class();
        if (isset(static::$cache[$class][$name]) || \array_key_exists($name, static::$cache[$class])) {
            return new static(static::$cache[$class][$name]);
        }

        throw new \BadMethodCallException("No static method or enum constant '$name' in class " . $class);
    }

    /**
     * It returns the short type name of the given FQCN. If the type name is not
     * found, it returns the given value.
     *
     * @param string $typeFqcn
     *
     * @return string
     * @throws \ReflectionException
     */
    public static function getTypeName($typeFqcn)
    {
        $class = \get_called_class();

        // needed to avoid collisions between immutable and non-immutable date types,
        // which are mapped to the same Symfony Form type classes
        $filteredNameToClassMap = array_filter(array_change_key_case(static::$cache[$class], CASE_LOWER),
            function ($typeName) {
                return !in_array($typeName, ['datetime_immutable', 'date_immutable', 'time_immutable']);
            }, ARRAY_FILTER_USE_KEY);

        return $filteredNameToClassMap[$typeFqcn] ?? $typeFqcn;
    }
}
