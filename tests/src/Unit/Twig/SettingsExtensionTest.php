<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Tests\Unit\Twig;

use PHPUnit\Framework\MockObject\MockObject;
use Harmony\Bundle\SettingsManagerBundle\Settings\SettingsRouter;
use Harmony\Bundle\SettingsManagerBundle\Twig\SettingsExtension;
use PHPUnit\Framework\TestCase;

class SettingsExtensionTest extends TestCase
{
    /**
     * @var SettingsRouter|MockObject
     */
    private $settingsRouter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->settingsRouter = $this
            ->getMockBuilder(SettingsRouter::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetFunctions()
    {
        $extension = new SettingsExtension($this->settingsRouter);
        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        /** @var \Twig\TwigFunction $function */
        $function = array_shift($functions);
        $this->assertInstanceOf(\Twig\TwigFunction::class, $function);
        $this->assertEquals('setting_get', $function->getName());
        $this->assertEquals('getSetting', $function->getCallable()[1]);
    }

    public function testGetSetting()
    {
        $this
            ->settingsRouter
            ->expects($this->once())
            ->method('get')
            ->with('foo_setting', 'hohoho')
            ->willReturn('cool');

        $extension = new SettingsExtension($this->settingsRouter);
        $value = $extension->getSetting('foo_setting', 'hohoho');

        $this->assertEquals('cool', $value);
    }
}
