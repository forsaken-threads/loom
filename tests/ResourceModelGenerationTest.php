<?php

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class ResourceModelGenerationTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    const RESOURCE_MODEL = 'TestResource';
    const RESOURCE_GROUP = 'TestResourceGroup';
    const RESOURCE_MODEL_EXISTING = 'User';

    /**
     * @var vfsStreamDirectory
     */
    protected $modelPath;

    /**
     * @var vfsStreamDirectory
     */
    protected $vfsRoot;

    public function setUp()
    {
        parent::setUp();

        $this->vfsRoot = vfsStream::setup('scratch', null, ['models' => []]);
        $this->modelPath = $this->vfsRoot->getChild('models');

        Webstuhl::setResourceModelBasePath($this->vfsRoot->getChild('models')->url());
    }

    public function testModelBasePath()
    {
        $this->assertEquals($this->modelPath->url(), Webstuhl::getResourceModelBasePath());
        $this->assertDirectoryExists(Webstuhl::getResourceModelBasePath());
    }

    public function testResourceControllerExists()
    {
        $this->assertFalse(Webstuhl::createEloquentModel(self::RESOURCE_MODEL_EXISTING));
    }

    public function testResourceModelGenerationWithoutGroup()
    {
        $this->assertTrue(Webstuhl::createEloquentModel(self::RESOURCE_MODEL));
        $this->assertTrue($this->modelPath->hasChild(self::RESOURCE_MODEL . '.php'));
        $this->seeInDatabase('webstuhl_resources', ['name' => Webstuhl::getResourceModelNamespace() . '\\' . self::RESOURCE_MODEL]);
    }

    public function testResourceModelGenerationWithGroup()
    {
        $this->assertTrue(Webstuhl::createEloquentModel(self::RESOURCE_MODEL, self::RESOURCE_GROUP));
        $this->assertTrue($this->modelPath->hasChild(self::RESOURCE_GROUP . DIRECTORY_SEPARATOR . self::RESOURCE_MODEL . '.php'));
        $this->seeInDatabase('webstuhl_resources', ['name' => Webstuhl::getResourceModelNamespace() . '\\' . self::RESOURCE_GROUP . '\\' . self::RESOURCE_MODEL]);
    }
}
