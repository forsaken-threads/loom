<?php

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class ResourceModelGenerationTest extends TestCase
{

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

        Loom::setResourceModelBasePath($this->vfsRoot->getChild('models')->url());
    }

    public function testModelBasePath()
    {
        $this->assertEquals($this->modelPath->url(), Loom::getResourceModelBasePath());
        $this->assertDirectoryExists(Loom::getResourceModelBasePath());
    }

    public function testResourceModelGenerationWithoutGroup()
    {
        $this->assertTrue(Loom::createEloquentModel('TestResource'));
        $this->assertTrue($this->modelPath->hasChild('TestResource.php'));
    }

    public function testResourceModelGenerationWithGroup()
    {
        $this->assertTrue(Loom::createEloquentModel('TestResource', 'TestResourceGroup'));
        $this->assertTrue($this->modelPath->hasChild('TestResourceGroup/TestResource.php'));
    }
}
