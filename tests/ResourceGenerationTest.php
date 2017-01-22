<?php

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class ResourceGenerationTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    const RESOURCE_MODEL = 'TestResource';
    const RESOURCE_GROUP = 'TestResourceGroup';
    const RESOURCE_MODEL_EXISTING = 'User';

    /**
     * @var vfsStreamDirectory
     */
    protected $resourcePath;

    /**
     * @var vfsStreamDirectory
     */
    protected $resourceRouteFilePath;

    /**
     * @var vfsStreamDirectory
     */
    protected $vfsRoot;

    public function setUp()
    {
        parent::setUp();

        $this->vfsRoot = vfsStream::setup('scratch', null, ['resources' => [], 'routes' => []]);
        $this->resourcePath = $this->vfsRoot->getChild('resources');
        $this->resourceRouteFilePath = $this->vfsRoot->getChild('routes');

        Webstuhl::setResourceBasePath($this->resourcePath->url());
        Webstuhl::setResourceRouteFilePath($this->resourceRouteFilePath->url());
    }

    public function testResourceBasePath()
    {
        $this->assertEquals($this->resourcePath->url(), Webstuhl::getResourceBasePath());
        $this->assertDirectoryExists(Webstuhl::getResourceBasePath());
    }

    public function testResourceRouteFilePath()
    {
        $this->assertEquals($this->resourceRouteFilePath->url(), Webstuhl::getResourceRouteFilePath());
        $this->assertDirectoryExists(Webstuhl::getResourceRouteFilePath());
    }

    public function testResourceExists()
    {
        $this->assertFalse(Webstuhl::createResource(self::RESOURCE_MODEL_EXISTING));
    }

    public function testResourceGenerationWithoutGroup()
    {
        $this->assertFalse($this->resourceRouteFilePath->hasChild('webstuhl.php'));
        $this->assertTrue(Webstuhl::createResource(self::RESOURCE_MODEL));
        $this->assertTrue($this->resourcePath->hasChild(self::RESOURCE_MODEL . '.php'));
        $this->assertTrue($this->resourceRouteFilePath->hasChild('webstuhl.php'));
        $this->seeInDatabase('webstuhl_resources', [
            'name' => Webstuhl::getResourceClassName(self::RESOURCE_MODEL),
            'url' => Webstuhl::getResourceUrl(self::RESOURCE_MODEL),
        ]);
    }

    public function testResourceModelGenerationWithGroup()
    {
        $this->assertFalse($this->resourceRouteFilePath->hasChild('webstuhl.php'));
        $this->assertTrue(Webstuhl::createResource(self::RESOURCE_MODEL, self::RESOURCE_GROUP));
        $this->assertTrue($this->resourcePath->hasChild(self::RESOURCE_GROUP . DIRECTORY_SEPARATOR . self::RESOURCE_MODEL . '.php'));
        $this->assertTrue($this->resourceRouteFilePath->hasChild('webstuhl.php'));
        $this->seeInDatabase('webstuhl_resources', [
            'name' => Webstuhl::getResourceClassName(self::RESOURCE_MODEL, self::RESOURCE_GROUP),
            'url' => Webstuhl::getResourceUrl(self::RESOURCE_MODEL, self::RESOURCE_GROUP),
        ]);
    }
}
