<?php

use Illuminate\Contracts\Console\Kernel;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class GenerateResourceCommandTest extends TestCase
{
    const BAD_RESOURCE_MODEL = '1';
    const BAD_RESOURCE_GROUP = '-';
    const RESOURCE_MODEL = 'TestResource';
    const RESOURCE_MODEL_RETRY = 'TestResource2';
    const RESOURCE_GROUP = 'TestGroup';
    const RESOURCE_NO_GROUP = '_';

    /**
     * @var MockInterface
     */
    protected $command;

    /**
     * @var vfsStreamDirectory
     */
    protected $controllerPath;

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

        $this->command = Mockery::mock('App\\Console\\Commands\\GenerateResource[ask,confirm,error]');
        $this->app[Kernel::class]->registerCommand($this->command);

        $this->vfsRoot = vfsStream::setup('scratch', null, ['models' => [], 'controllers' => []]);
        $this->controllerPath = $this->vfsRoot->getChild('controllers');
        $this->modelPath = $this->vfsRoot->getChild('models');

        Loom::setResourceControllerBasePath($this->controllerPath->url());
        Loom::setResourceModelBasePath($this->modelPath->url());
    }

    public function testGoodNameGoodGroup()
    {
        $this->command->shouldReceive('ask')
            ->once()
            ->with(trans('commands/generate-resource.ask-for-name'))
            ->andReturn(self::RESOURCE_MODEL);

        $this->command->shouldReceive('ask')
            ->once()
            ->with(trans('commands/generate-resource.ask-for-group'))
            ->andReturn(self::RESOURCE_GROUP);

        $this->command->shouldReceive('confirm')
            ->once()
            ->with(trans('commands/generate-resource.group-confirm-create'))
            ->andReturn(true);

        $this->command->shouldNotReceive('error');

        $this->artisan('make:resource');

        Mockery::close();

        $this->assertTrue($this->controllerPath->hasChild(self::RESOURCE_GROUP . DIRECTORY_SEPARATOR . self::RESOURCE_MODEL . 'Controller.php'));
        $this->assertTrue($this->modelPath->hasChild(self::RESOURCE_GROUP . DIRECTORY_SEPARATOR . self::RESOURCE_MODEL . '.php'));

        $this->vfsRoot->removeChild('models/' . self::RESOURCE_GROUP);
        $this->vfsRoot->removeChild('controllers/' . self::RESOURCE_GROUP);

        $this->controllerPath->removeChild(self::RESOURCE_GROUP);
        $this->modelPath->removeChild(self::RESOURCE_GROUP);
    }

    public function testGoodNameGoodGroupRejectGroupCreation()
    {
        Loom::shouldReceive('getResourceModelBasePath')->once()->andReturn($this->modelPath->url());
        Loom::shouldReceive('createEloquentModel')->never();
        Loom::shouldReceive('createResourceController')->never();
        Loom::shouldReceive('resourceControllerExists')->never();
        Loom::shouldReceive('resourceModelExists')->never();

        $this->command->shouldReceive('ask')
            ->once()
            ->with(trans('commands/generate-resource.ask-for-name'))
            ->andReturn(self::RESOURCE_MODEL);

        $this->command->shouldReceive('ask')
            ->once()
            ->with(trans('commands/generate-resource.ask-for-group'))
            ->andReturn(self::RESOURCE_GROUP);

        $this->command->shouldReceive('confirm')
            ->once()
            ->with(trans('commands/generate-resource.group-confirm-create'))
            ->andReturn(false);

        $this->command->shouldReceive('confirm')
            ->once()
            ->with(trans('commands/generate-resource.try-again'))
            ->andReturn(false);

        $this->command->shouldNotReceive('error');

        $this->artisan('make:resource');

        Mockery::close();
    }

    public function testGoodNameNoGroup()
    {
        $this->command->shouldReceive('ask')
            ->once()
            ->with(trans('commands/generate-resource.ask-for-name'))
            ->andReturn(self::RESOURCE_MODEL);

        $this->command->shouldReceive('ask')
            ->once()
            ->with(trans('commands/generate-resource.ask-for-group'))
            ->andReturn(self::RESOURCE_NO_GROUP);

        $this->command->shouldNotReceive('error');

        $this->artisan('make:resource');

        Mockery::close();

        $this->assertTrue($this->controllerPath->hasChild(self::RESOURCE_MODEL . 'Controller.php'));
        $this->assertTrue($this->modelPath->hasChild(self::RESOURCE_MODEL . '.php'));

        $this->controllerPath->removeChild(self::RESOURCE_GROUP);
        $this->modelPath->removeChild(self::RESOURCE_GROUP);
    }

    public function testBadName()
    {
        Loom::shouldReceive('createEloquentModel')->never();
        Loom::shouldReceive('createResourceController')->never();

        $this->command->shouldReceive('ask')
            ->once()
            ->with(trans('commands/generate-resource.ask-for-name'))
            ->andReturn(self::BAD_RESOURCE_MODEL);

        $this->command->shouldReceive('error')
            ->once()
            ->with(trans('commands/generate-resource.name-validation-error'));

        $this->command->shouldReceive('confirm')
            ->once()
            ->with(trans('commands/generate-resource.try-again'))
            ->andReturn(false);

        $this->artisan('make:resource', ['--no-interaction' => true]);

        Mockery::close();
    }

    public function testBadNameRetry()
    {

        Loom::shouldReceive('createEloquentModel')->never();
        Loom::shouldReceive('createResourceController')->never();

        $this->command->shouldReceive('ask')
            ->twice()
            ->with(trans('commands/generate-resource.ask-for-name'))
            ->andReturn(self::BAD_RESOURCE_MODEL);

        $this->command->shouldReceive('error')
            ->twice()
            ->with(trans('commands/generate-resource.name-validation-error'));

        $this->command->shouldReceive('confirm')
            ->once()
            ->with(trans('commands/generate-resource.try-again'))
            ->andReturn(true);

        $this->command->shouldReceive('confirm')
            ->once()
            ->with(trans('commands/generate-resource.try-again'))
            ->andReturn(false);

        $this->artisan('make:resource', ['--no-interaction' => true]);

        Mockery::close();
    }

    public function testBadNameRetryWithGoodName()
    {

        $this->command->shouldReceive('ask')
            ->once()
            ->with(trans('commands/generate-resource.ask-for-name'))
            ->andReturn(self::BAD_RESOURCE_MODEL);

        $this->command->shouldReceive('ask')
            ->once()
            ->with(trans('commands/generate-resource.ask-for-name'))
            ->andReturn(self::RESOURCE_MODEL_RETRY);

        $this->command->shouldReceive('error')
            ->once()
            ->with(trans('commands/generate-resource.name-validation-error'));

        $this->command->shouldReceive('confirm')
            ->once()
            ->with(trans('commands/generate-resource.try-again'))
            ->andReturn(true);

        $this->command->shouldReceive('ask')
            ->once()
            ->with(trans('commands/generate-resource.ask-for-group'))
            ->andReturn(self::RESOURCE_NO_GROUP);

        $this->artisan('make:resource', ['--no-interaction' => true]);

        Mockery::close();

        $this->assertTrue($this->controllerPath->hasChild(self::RESOURCE_MODEL_RETRY . 'Controller.php'));
        $this->assertTrue($this->modelPath->hasChild(self::RESOURCE_MODEL_RETRY . '.php'));

        $this->controllerPath->removeChild(self::RESOURCE_GROUP);
        $this->modelPath->removeChild(self::RESOURCE_GROUP);
    }

    public function testGoodNameBadGroup()
    {
        Loom::shouldReceive('createEloquentModel')->never();
        Loom::shouldReceive('createResourceController')->never();

        $this->command->shouldReceive('ask')
            ->once()
            ->with(trans('commands/generate-resource.ask-for-name'))
            ->andReturn(self::RESOURCE_MODEL);

        $this->command->shouldReceive('ask')
            ->once()
            ->with(trans('commands/generate-resource.ask-for-group'))
            ->andReturn(self::BAD_RESOURCE_GROUP);

        $this->command->shouldReceive('error')
            ->once()
            ->with(trans('commands/generate-resource.group-validation-error'));

        $this->command->shouldReceive('confirm')
            ->once()
            ->with(trans('commands/generate-resource.try-again'))
            ->andReturn(false);

        $this->artisan('make:resource', ['--no-interaction' => true]);

        Mockery::close();
    }

    public function testGoodNameBadGroupRetry()
    {
        Loom::shouldReceive('createEloquentModel')->never();
        Loom::shouldReceive('createResourceController')->never();

        $this->command->shouldReceive('ask')
            ->once()
            ->with(trans('commands/generate-resource.ask-for-name'))
            ->andReturn(self::RESOURCE_MODEL);

        $this->command->shouldReceive('ask')
            ->twice()
            ->with(trans('commands/generate-resource.ask-for-group'))
            ->andReturn(self::BAD_RESOURCE_GROUP);

        $this->command->shouldReceive('error')
            ->twice()
            ->with(trans('commands/generate-resource.group-validation-error'));

        $this->command->shouldReceive('confirm')
            ->once()
            ->with(trans('commands/generate-resource.try-again'))
            ->andReturn(true);

        $this->command->shouldReceive('confirm')
            ->once()
            ->with(trans('commands/generate-resource.try-again'))
            ->andReturn(false);

        $this->artisan('make:resource', ['--no-interaction' => true]);

        Mockery::close();
    }

}
