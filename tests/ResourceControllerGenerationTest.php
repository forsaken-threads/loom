<?php

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class ResourceControllerGenerationTest extends TestCase
{

    /**
     * @var vfsStreamDirectory
     */
    protected $controllerPath;

    /**
     * @var vfsStreamDirectory
     */
    protected $vfsRoot;

    public function setUp()
    {
        parent::setUp();

        $this->vfsRoot = vfsStream::setup('scratch', null, ['controllers' => []]);
        $this->controllerPath = $this->vfsRoot->getChild('controllers');

        Loom::setResourceControllerBasePath($this->vfsRoot->getChild('controllers')->url());

    }

    public function testControllerBasePath()
    {
        $this->assertEquals($this->controllerPath->url(), Loom::getResourceControllerBasePath());
        $this->assertDirectoryExists(Loom::getResourceControllerBasePath());
    }

    public function testResourceControllerGenerationWithoutGroup()
    {
        $this->assertTrue(Loom::createResourceController('TestResource'));
        $this->assertTrue($this->controllerPath->hasChild('TestResourceController.php'));
    }

    public function testResourceControllerGenerationWithGroup()
    {
        $this->assertTrue(Loom::createResourceController('TestResource', 'TestControllerGroup'));
        $this->assertTrue($this->controllerPath->hasChild('TestControllerGroup/TestResourceController.php'));
    }
}
