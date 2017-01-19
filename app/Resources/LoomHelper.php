<?php

namespace App\Resources;

class LoomHelper
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
     * Absolute file path to resource models
     * @var string
     */
    protected $modelBasePath;

    /**
     * Namespace for resource models
     * @var string
     */
    protected $modelNamespace;

    /**
     * @param $name
     * @param null $group
     * @return bool
     */
    public function createEloquentModel($name, $group = null)
    {
        if ($this->resourceModelExists($name, $group)) {
            return false;
        }

        if ($group && !is_dir($this->getResourceModelBasePath($group)) && !mkdir($this->getResourceModelBasePath($group))) {
            return false;
        }

        $data = [
            'name' => $name,
            'group' => $group ? '\\' . $group : '',
        ];
        $content = view('commands.generate-resource.eloquent-model', $data)->__toString();

        $file = $name . '.php';
        if ($group) {
            $file = "$group/$file";
        }

        if (!file_put_contents($this->getResourceModelBasePath() . "/$file", $content)) {
            return false;
        }

        return true;
    }

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
    public function getResourceModelBasePath($path = '')
    {
        return $this->modelBasePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * @return string
     */
    public function getResourceModelNamespace()
    {
        return $this->modelNamespace;
    }

    /**
     * @param $name
     * @param null $group
     * @return bool
     */
    public function resourceControllerExists($name, $group = null)
    {
        $resource = $this->controllerNamespace . '\\' . ($group ? $group . '\\' : '') . $name;
        return class_exists($resource);
    }

    /**
     * @param $name
     * @param null $group
     * @return bool
     */
    public function resourceModelExists($name, $group = null)
    {
        $resource = $this->modelNamespace . '\\' . ($group ? $group . '\\' : '') . $name;
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
    public function setResourceModelBasePath($directory)
    {
        $this->modelBasePath = $directory;
        return $this;
    }

    /**
     * @param $namespace
     * @return $this
     */
    public function setResourceModelNamespace($namespace)
    {
        $this->modelNamespace = $namespace;
        return $this;
    }
}