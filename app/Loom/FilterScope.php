<?php

namespace App\Loom;

use App\Contracts\FilterContract;
use App\Contracts\QualityControlContract;
use Illuminate\Database\Eloquent\Builder;

class FilterScope implements FilterContract
{
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
    protected $input = [];

    /**
     * @var array
     */
    protected $inputValues = [];

    /**
     * @var string
     */
    protected $name;

    /**
     * @var bool
     */
    protected $orTogether;

    /**
     * @var string|callable
     */
    protected $presentation;

    /**
     * @var array
     */
    protected $validationRules = [];

    /**
     * @param array $givenScopes
     * @param FilterCollection $collection
     * @param QualityControlContract $qualityControl
     */
    public static function collect(array $givenScopes, FilterCollection $collection, QualityControlContract $qualityControl)
    {
        foreach ($givenScopes as $scope => $arguments) {
            if (ctype_digit($scope . '')) {
                $scope = $arguments;
                $arguments = [];
            }
            if ($filterScope = $qualityControl->getFilterScope($scope)) {
                if ($filterScope->validateAndSetInput($arguments)) {
                    $filterScope->setOrTogether($collection->isOrTogether());
                    $collection->addFilter(trans('quality-control.filterable.applied-scope'). ':' . $scope, $filterScope);
                }
            }
        }
    }

    /**
     * FilterScope constructor.
     *
     * @param $name
     * @param bool $orTogether
     */
    public function __construct($name, $orTogether = false)
    {
        $this->name = $name;
        $this->presentation = $name;
        $this->orTogether = $orTogether;
    }

    /**
     * @param Builder $query
     */
    public function applyFilter(Builder $query)
    {
        if ($this->orTogether) {
            $query->orWhere(function ($q) {
                /** @var Builder $q */
                $q->{$this->name}(...$this->inputValues);
            });
        } else {
            $query->{$this->name}(...$this->inputValues);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function presentFilter()
    {
        if (is_callable($this->presentation)) {
            return call_user_func($this->presentation, $this->input);
        }
        return $this->presentation;
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
     * @param $orTogether
     * @return $this
     */
    public function setOrTogether($orTogether)
    {
        $this->orTogether = (bool) $orTogether;
        return $this;
    }

    /**
     * @param string|callable $presentation
     * @return $this
     */
    public function setPresentation($presentation)
    {
        $this->presentation = $presentation;
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
    public function validateAndSetInput($givenInput)
    {
        $testInput = [];
        foreach ($this->arguments as $argument) {
            if (key_exists($argument, $givenInput)) {
                $testInput[$argument] = $givenInput[$argument];
            } elseif (key_exists($argument, $this->defaultValues)) {
                $testInput[$argument] = $this->defaultValues[$argument];
            }
        }
        if (validator($testInput, $this->validationRules)->passes()) {
            $this->input = $testInput;
            $this->inputValues = array_values($testInput);
            return true;
        }
        return false;
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