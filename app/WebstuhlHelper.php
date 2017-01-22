<?php

namespace App;

use App\Resources\WebstuhlResource;
use DB;
use Log;

class WebstuhlHelper
{

    /**
     * Absolute file path to resource controllers
     * @var string
     */
    protected $controllerBasePath;

    /**
     * Namespace for resource controllers
     * @var string
     */
    protected $controllerNamespace;

    /**
     * Absolute file path to resource
     * @var string
     */
    protected $resourceBasePath;

    /**
     * Namespace for resource
     * @var string
     */
    protected $resourceNamespace;

    /**
     * Absolute file path to resource route file directory
     * @var string
     */
    protected $resourceRouteFilePath;

    /**
     * @param $name
     * @param null $group
     * @return bool
     */
    public function createResource($name, $group = null)
    {
        if ($this->resourceExists($name, $group)) {
            return false;
        }

        if ($group && !is_dir($this->getResourceBasePath($group)) && !mkdir($this->getResourceBasePath($group))) {
            return false;
        }

        $data = [
            'name' => $name,
            'group' => $group ? '\\' . $group : '',
        ];
        $content = view('commands.generate-resource.resource', $data)->__toString();

        $file = $name . '.php';
        if ($group) {
            $file = "$group/$file";
        }

        DB::beginTransaction();
        try {
            WebstuhlResource::create([
                'name' => $this->getResourceClassName($name, $group),
                'url' => $this->getResourceUrl($name, $group),
            ]);
            if (!file_put_contents($this->getResourceBasePath() . "/$file", $content)) {
                DB::rollBack();
                return false;
            }
            if (!file_put_contents($this->getResourceRouteFilePath('webstuhl.php'), view('commands.generate-resource.resource-routes'))) {
                DB::rollBack();
                return false;
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage(), [__FUNCTION__ => func_get_args()]);
            DB::rollBack();
            return false;
        }
        DB::commit();

        return true;
    }

    /**
     * @param $name
     * @param null $group
     * @return bool
     */
    public function createResourceController($name, $group = null)
    {
        if ($this->resourceControllerExists($name, $group)) {
            return false;
        }

        if ($group && !is_dir($this->getResourceControllerBasePath($group)) && !mkdir($this->getResourceControllerBasePath($group))) {
            return false;
        }

        $data = [
            'name' => $name,
            'group' => $group ? '\\' . $group : '',
        ];
        $content = view('commands.generate-resource.resource-controller', $data)->__toString();

        $file = $name . 'Controller.php';
        if ($group) {
            $file = "$group/$file";
        }

        if (!file_put_contents($this->getResourceControllerBasePath() . "/$file", $content)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $path
     * @return mixed
     */
    public function getResourceControllerBasePath($path = '')
    {
        return $this->controllerBasePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * @return string
     */
    public function getResourceControllerNamespace()
    {
        return $this->controllerNamespace;
    }

    /**
     * @param string $path
     * @return mixed
     */
    public function getResourceBasePath($path = '')
    {
        return $this->resourceBasePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * @param $name
     * @param null $group
     * @return string
     */
    public function getResourceClassName($name, $group = null)
    {
        return $this->resourceNamespace . '\\' . ($group ? $group . '\\' : '') . $name;
    }

    /**
     * @return string
     */
    public function getResourceNamespace()
    {
        return $this->resourceNamespace;
    }

    /**
     * @param string $path
     * @return string
     */
    public function getResourceRouteFilePath($path = '')
    {
        return $this->resourceRouteFilePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * @param $name
     * @param null $group
     * @return string
     */
    public function getResourceUrl($name, $group = null)
    {
        return ($group ? snake_case($group) . '\\' : '') . str_plural(snake_case($name));
    }

    /**
     * @return bool
     */
    public function isWeaving()
    {
        return !empty($_ENV['webstuhl-is-weaving']);
    }

    /**
     * @param $name
     * @param null $group
     * @return bool
     */
    public function resourceControllerExists($name, $group = null)
    {
        $resource = $this->controllerNamespace . '\\' . ($group ? $group . '\\' : '') . $name . 'Controller';
        return class_exists($resource);
    }

    /**
     * @param $name
     * @param null $group
     * @return bool
     */
    public function resourceExists($name, $group = null)
    {
        $resource = $this->resourceNamespace . '\\' . ($group ? $group . '\\' : '') . $name;
        return class_exists($resource);
    }

    /**
     * @param $directory
     * @return $this
     */
    public function setResourceControllerBasePath($directory)
    {
        $this->controllerBasePath = $directory;
        return $this;
    }

    /**
     * @param $namespace
     * @return $this
     */
    public function setResourceControllerNamespace($namespace)
    {
        $this->controllerNamespace = $namespace;
        return $this;
    }

    /**
     * @param $directory
     * @return $this
     */
    public function setResourceBasePath($directory)
    {
        $this->resourceBasePath = $directory;
        return $this;
    }

    /**
     * @param $namespace
     * @return $this
     */
    public function setResourceNamespace($namespace)
    {
        $this->resourceNamespace = $namespace;
        return $this;
    }

    /**
     * @param $directory
     */
    public function setResourceRouteFilePath($directory)
    {
        $this->resourceRouteFilePath = $directory;
    }
}