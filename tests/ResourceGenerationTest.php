<?php

namespace ForsakenThreads\Loom\Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Loom;

class ResourceGenerationTest extends TestCase
{
    use DatabaseTransactions;

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

    public function setUp($withTestResources = true)
    {
        parent::setUp(false);

        $this->vfsRoot = vfsStream::setup('scratch', null, ['resources' => [], 'routes' => []]);
        $this->resourcePath = $this->vfsRoot->getChild('resources');
        $this->resourceRouteFilePath = $this->vfsRoot->getChild('routes');

        Loom::setResourceBasePath($this->resourcePath->url());
        Loom::setResourceRouteFilePath($this->resourceRouteFilePath->url());
    }

    public function testResourceBasePath()
    {
        $this->assertEquals($this->resourcePath->url(), Loom::getResourceBasePath());
        $this->assertDirectoryExists(Loom::getResourceBasePath());
    }

    public function testResourceRouteFilePath()
    {
        $this->assertEquals($this->resourceRouteFilePath->url(), Loom::getResourceRouteFilePath());
        $this->assertDirectoryExists(Loom::getResourceRouteFilePath());
    }

    public function testResourceExists()
    {
        $this->assertFalse(Loom::createResource(self::RESOURCE_MODEL_EXISTING));
    }

    public function testResourceGenerationWithoutGroup()
    {
        $this->assertFalse($this->resourceRouteFilePath->hasChild('loom.php'));
        $this->assertTrue(Loom::createResource(self::RESOURCE_MODEL));
        $this->assertTrue($this->resourcePath->hasChild(self::RESOURCE_MODEL . '.php'));
        $this->assertTrue($this->resourceRouteFilePath->hasChild('loom.php'));
        $this->assertDatabaseHas('loom_resources', [
            'name' => Loom::getResourceClassName(self::RESOURCE_MODEL),
            'url' => Loom::getResourceUrl(self::RESOURCE_MODEL),
        ]);
    }

    public function testResourceModelGenerationWithGroup()
    {
        $this->assertFalse($this->resourceRouteFilePath->hasChild('loom.php'));
        $this->assertTrue(Loom::createResource(self::RESOURCE_MODEL, self::RESOURCE_GROUP));
        $this->assertTrue($this->resourcePath->hasChild(self::RESOURCE_GROUP . DIRECTORY_SEPARATOR . self::RESOURCE_MODEL . '.php'));
        $this->assertTrue($this->resourceRouteFilePath->hasChild('loom.php'));
        $this->assertDatabaseHas('loom_resources', [
            'name' => Loom::getResourceClassName(self::RESOURCE_MODEL, self::RESOURCE_GROUP),
            'url' => Loom::getResourceUrl(self::RESOURCE_MODEL, self::RESOURCE_GROUP),
        ]);
    }
}
