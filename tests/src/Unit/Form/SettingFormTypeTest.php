<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Tests\Unit\Form;

use Harmony\Bundle\SettingsManagerBundle\Form\SettingFormType;
use Harmony\Bundle\SettingsManagerBundle\Model\DomainModel;
use Harmony\Bundle\SettingsManagerBundle\Model\SettingModel;
use Harmony\Bundle\SettingsManagerBundle\Model\Type;
use Symfony\Component\Form\Test\TypeTestCase;

class SettingFormTypeTest extends TypeTestCase
{
    public function submitValidDataProvider()
    {
        // test bool submit
        $data1 = new SettingModel();
        $data1->setType(Type::BOOL());

        $formData1 = [
            'name' => 'foo',
            'description' => 'lorem ipsum',
            'data' => 1,
        ];

        $object1 = new SettingModel();
        $object1
            ->setDescription('lorem ipsum')
            ->setDomain(new DomainModel())
            ->setType(Type::BOOL())
            ->setData(true);

        yield [$formData1, $data1, $object1];

        // test string submit
        $data2 = new SettingModel();
        $data2->setType(Type::STRING());

        $formData2 = [
            'name' => 'foo',
            'data' => 2.5678,
        ];

        $object2 = new SettingModel();
        $object2
            ->setType(Type::STRING())
            ->setDomain(new DomainModel())
            ->setData('2.5678');

        yield [$formData2, $data2, $object2];

        // test float submit
        $data3 = new SettingModel();
        $data3->setType(Type::FLOAT());

        $formData3 = [
            'name' => 'foo',
            'data' => 2.5678,
        ];

        $object3 = new SettingModel();
        $object3
            ->setType(Type::FLOAT())
            ->setDomain(new DomainModel())
            ->setData(2.57);

        yield [$formData3, $data3, $object3];

        // test integer submit
        $data4 = new SettingModel();
        $data4->setType(Type::INT());

        $formData4 = [
            'name' => 'foo',
            'data' => 2.5678,
        ];

        $object4 = new SettingModel();
        $object4
            ->setType(Type::INT())
            ->setDomain(new DomainModel())
            ->setData(2);

        yield [$formData4, $data4, $object4];
    }

    /**
     * @param array        $formData
     * @param SettingModel $data
     * @param SettingModel $expectedObject
     *
     * @dataProvider submitValidDataProvider
     */
    public function testSubmitValidData(array $formData, SettingModel $data, SettingModel $expectedObject)
    {
        $form = $this->factory->create(SettingFormType::class, $data);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedObject, $form->getData());
    }
}
