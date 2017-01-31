<?php

namespace App\Loom;

class FilterScope
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * @var array
     */
    protected $defaultValues = [];

    /**
     * @var array
     */
    protected $validationRules = [];

    /**
     * FilterScope constructor.
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @param $argument
     * @param $default
     * @return $this
     */
    public function setArgumentDefault($argument, $default)
    {
        $this->defaultValues[$argument] = $default;
        return $this;
    }

    /**
     * @param $validationRules
     * @return $this
     */
    public function setValidationRules($validationRules)
    {
        $this->validationRules = $validationRules;
        return $this;
    }

    /**
     * @param $givenInput
     * @return bool
     */
    public function validateInput($givenInput)
    {
        $testInput = [];
        // TODO: throw validation exception
        foreach ($this->arguments as $argument) {
            if (key_exists($argument, $givenInput)) {
                $testInput[$argument] = $givenInput[$argument];
            } elseif (key_exists($argument, $this->defaultValues)) {
                $testInput[$argument] = $this->defaultValues[$argument];
            }
        }
        return validator($testInput, $this->validationRules)->passes();

    }

    /**
     * @param array ...$arguments
     * @return $this
     */
    public function withArguments(...$arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }
}