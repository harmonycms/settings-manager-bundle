<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Tests\Unit\Form;

use Harmony\Bundle\SettingsManagerBundle\Form\SettingFormType;
use Harmony\Bundle\SettingsManagerBundle\Model\SettingDomain;
use Harmony\Bundle\SettingsManagerBundle\Model\Setting;
use Harmony\Bundle\SettingsManagerBundle\Model\Type;
use Symfony\Component\Form\Test\TypeTestCase;

class SettingFormTypeTest extends TypeTestCase
{
    public function submitValidDataProvider()
    {
        // test bool submit
        $data1 = new Setting();
        $data1->setType(Type::BOOL());

        $formData1 = [
            'name' => 'foo',
            'description' => 'lorem ipsum',
            'data' => 1,
        ];

        $object1 = new Setting();
        $object1
            ->setDescription('lorem ipsum')
            ->setDomain(new SettingDomain())
            ->setType(Type::BOOL())
            ->setData(true);

        yield [$formData1, $data1, $object1];

        // test string submit
        $data2 = new Setting();
        $data2->setType(Type::STRING());

        $formData2 = [
            'name' => 'foo',
            'data' => 2.5678,
        ];

        $object2 = new Setting();
        $object2
            ->setType(Type::STRING())
            ->setDomain(new SettingDomain())
            ->setData('2.5678');

        yield [$formData2, $data2, $object2];

        // test float submit
        $data3 = new Setting();
        $data3->setType(Type::FLOAT());

        $formData3 = [
            'name' => 'foo',
            'data' => 2.5678,
        ];

        $object3 = new Setting();
        $object3
            ->setType(Type::FLOAT())
            ->setDomain(new SettingDomain())
            ->setData(2.57);

        yield [$formData3, $data3, $object3];

        // test integer submit
        $data4 = new Setting();
        $data4->setType(Type::INT());

        $formData4 = [
            'name' => 'foo',
            'data' => 2.5678,
        ];

        $object4 = new Setting();
        $object4
            ->setType(Type::INT())
            ->setDomain(new SettingDomain())
            ->setData(2);

        yield [$formData4, $data4, $object4];
    }

    /**
     * @param array   $formData
     * @param Setting $data
     * @param Setting $expectedObject
     *
     * @dataProvider submitValidDataProvider
     */
    public function testSubmitValidData(array $formData, Setting $data, Setting $expectedObject)
    {
        $form = $this->factory->create(SettingFormType::class, $data);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedObject, $form->getData());
    }
}
