<?php

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class ResourceControllerGenerationTest extends TestCase
{

    const RESOURCE_MODEL = 'TestResource';
    const RESOURCE_MODEL_EXISTING = 'User';
    const RESOURCE_GROUP = 'TestGroup';

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

        Webstuhl::setResourceControllerBasePath($this->vfsRoot->getChild('controllers')->url());

    }

    public function testControllerBasePath()
    {
        $this->assertEquals($this->controllerPath->url(), Webstuhl::getResourceControllerBasePath());
        $this->assertDirectoryExists(Webstuhl::getResourceControllerBasePath());
    }

    public function testResourceControllerGenerationWithoutGroup()
    {
        $this->assertTrue(Webstuhl::createResourceController(self::RESOURCE_MODEL));
        $this->assertTrue($this->controllerPath->hasChild(self::RESOURCE_MODEL . 'Controller.php'));
    }

    public function testResourceControllerExists()
    {
        $this->assertFalse(Webstuhl::createResourceController(self::RESOURCE_MODEL_EXISTING));
    }

    public function testResourceControllerGenerationWithGroup()
    {
        $this->assertTrue(Webstuhl::createResourceController(self::RESOURCE_MODEL, self::RESOURCE_GROUP));
        $this->assertTrue($this->controllerPath->hasChild(self::RESOURCE_GROUP . DIRECTORY_SEPARATOR . self::RESOURCE_MODEL . 'Controller.php'));
    }
}
