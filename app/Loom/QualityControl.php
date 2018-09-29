<?php

namespace App\Loom;

use App\Contracts\QualityControlContract;
use App\Exceptions\QualityControlException;
use App\Traits\QualityControllable;

class QualityControl implements QualityControlContract
{

    /**
     * These are the base validation rules for the resource.
     * @var array
     */
    protected $defaultRules;

    /**
     * Other resources that can be connected to the resource are managed
     * through Quality Control.
     * @var array
     */
    protected $connectableResources = [];

    /**
     * These transforms are applied to the defaultRules to get the rules
     * for a specific context.
     * @var array
     */
    protected $contextualTransformations = ['__default' => []];

    /**
     * This is a flag for the currently edited context
     * @var string
     */
    protected $editingContext;

    /**
     * Scopes that can be applied to resource indexes are managed through
     * Quality Control.
     * @var FilterScope[]
     */
    protected $filterScopes = [];

    /**
     * These messages are to customize the validator responses.
     * @var array
     */
    protected $messages = ['__default' => []];

    /**
     * Rules for belongsToMany pivot tables.
     * These rules, if needed, can be set one or the other of the
     * two connected resources.
     * @var array
     */
    protected $pivots = ['__default' => []];

    /**
     * @var QualityControllable|bool $resource
     */
    protected $resource = false;

    /**
     * QualityControl constructor.
     * @param array $defaultRules
     */
    public function __construct(array $defaultRules = [])
    {
        $this->defaultRules = $defaultRules;
        $this->editingContext = '__default';
    }


    /**
     * @param $fields
     * @param $rule
     * @param null $pivotResource
     * @return $this
     */
    public function append($fields, $rule, $pivotResource = null)
    {
        if (!is_array($fields)) {
            $fields = (array) $fields;
        }
        foreach ($fields as $field) {
            if (!$pivotResource) {
                $this->contextualTransformations[$this->editingContext][] = ['append' => [$field, $rule]];
            } else {
                $this->pivots[$this->editingContext][$pivotResource][] = ['append' => [$field, $rule]];
            }
        }
        return $this;
    }

    /**
     * @param $rules
     * @param null $pivotResource
     * @return $this
     */
    public function appendAll($rules, $pivotResource = null)
    {
        if (!$pivotResource) {
            $this->contextualTransformations[$this->editingContext][] = ['appendAll' => $rules];
        } else {
            $this->pivots[$this->editingContext][$pivotResource][] = ['appendAll' => $rules];
        }
        return $this;
    }

    /**
     * Set the currently editing context
     * @param $context
     * @return $this
     */
    public function forContext($context)
    {
        $this->editingContext = $context;
        if (!isset($this->contextualTransformations[$context])) {
            $this->contextualTransformations[$context] = [];
        }
        if (!isset($this->messages[$context])) {
            $this->messages[$context] = [];
        }
        if (!isset($this->pivots[$context])) {
            $this->pivots[$context] = [];
        }
        return $this;
    }

    /**
     * @param string $resource
     * @return QualityControllable|bool
     */
    public function getConnectableResource($resource)
    {
        if (empty($this->resource) || !is_string($resource) || !in_array($resource, $this->connectableResources) || !method_exists($this->resource, $resource)) {
            return false;
        }
        return $this->resource->$resource()->getRelated();
    }

    /**
     * @return array
     */
    public function getConnectableResources()
    {
        return $this->connectableResources;
    }

    /**
     * @param string $resource
     * @param null|string $context
     * @return Inspections|bool
     */
    public function getFilterPivot($resource, $context = null)
    {
        if (empty($context)) {
            $context = '__default';
        }

        $rules = !empty($this->pivots['__default'][$resource])
            ? $this->pivots['__default'][$resource]
            : [];

        if ($context != '__default' && !empty($this->pivots[$context][$resource])) {
            $rules = $this->applyTransformations($rules, $this->pivots[$context][$resource]);
        }

        return new Inspections($rules);
    }

    /**
     * @param $scopeName
     * @return bool|FilterScope
     */
    public function getFilterScope($scopeName)
    {
        if (isset($this->filterScopes[$scopeName])) {
            return $this->filterScopes[$scopeName];
        }
        return false;
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages[$this->editingContext];
    }

    /**
     * @return QualityControllable|bool
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param null $context
     * @return Inspections
     * @throws QualityControlException
     */
    public function getRules($context = null)
    {
        if (empty($context)) {
            $context = '__default';
        }

        $rules = $this->defaultRules;

        if (!empty($this->contextualTransformations[$context])) {
            $rules = $this->applyTransformations($rules, $this->contextualTransformations[$context]);
        }

        if ($collisions = array_intersect(array_keys($rules), $this->connectableResources)) {
            throw new QualityControlException(trans('quality-control.filterable.property-connectable-resource-collision', ['collisions' => implode(',', $collisions)]));
        }

        return new Inspections($rules);
    }

    /**
     * @param $fields
     * @param null $pivotResource
     * @return $this
     */
    public function remove($fields, $pivotResource = null)
    {
        if (!is_array($fields)) {
            $fields = (array) $fields;
        }
        if (!$pivotResource) {
            $this->contextualTransformations[$this->editingContext][] = ['remove' => $fields];
        } else {
            $this->pivots[$this->editingContext][$pivotResource][] = ['remove' => $fields];
        }
        return $this;
    }

    /**
     * @param $fields
     * @param $rule
     * @param null $pivotResource
     * @return $this
     */
    public function replace($fields, $rule, $pivotResource = null)
    {
        if (!is_array($fields)) {
            $fields = (array) $fields;
        }
        foreach ($fields as $field) {
            if (!$pivotResource) {
                $this->contextualTransformations[$this->editingContext][] = ['replace' => [$field, $rule]];
            } else {
                $this->pivots[$this->editingContext][$pivotResource][] = ['replace' => [$field, $rule]];
            }
        }
        return $this;
    }

    /**
     * @param null $pivotResource
     * @return $this
     */
    public function requireAll($pivotResource = null)
    {
        if (!$pivotResource) {
            $this->contextualTransformations[$this->editingContext][] = ['requireAll' => true];
        } else {
            $this->pivots[$this->editingContext][$pivotResource][] = ['requireAll' => true];
        }
        return $this;
    }

    /**
     * @param $fields
     * @param null $pivotResource
     * @return $this
     */
    public function requireExcept($fields, $pivotResource = null)
    {
        if (!is_array($fields)) {
            $fields = (array) $fields;
        }
        $this->append(array_diff(array_keys($this->defaultRules), $fields), 'required', $pivotResource);
        return $this;
    }

    /**
     * @param $fields
     * @param null $pivotResource
     * @return $this
     */
    public function requireOnly($fields, $pivotResource = null)
    {
        if (!is_array($fields)) {
            $fields = (array) $fields;
        }
        $this->append($fields, 'required', $pivotResource);
        return $this;
    }

    /**
     * @param QualityControllable $resource
     * @param array $connectableResources
     * @return $this
     */
    public function setConnectableResources($resource, $connectableResources)
    {
        $this->resource = $resource;
        $this->connectableResources = $connectableResources;
        return $this;
    }

    /**
     * @param $messages
     * @return $this
     */
    public function withMessages($messages)
    {
        $this->messages[$this->editingContext] = $messages;
        return $this;
    }

    /**
     * @param string $resource
     * @param $rules
     * @return $this
     */
    public function withPivot($resource, $rules)
    {
        $this->pivots[$this->editingContext][$resource] = $rules;
        return $this;
    }

    /**
     * @param string|FilterScope $scope
     * @return $this
     */
    public function withScope($scope)
    {
        if (is_string($scope)) {
            $this->filterScopes[$scope] = new FilterScope($scope);
        } elseif ($scope instanceof FilterScope) {
            $this->filterScopes[$scope->getName()] = $scope;
        }
        return $this;
    }

    /**
     * @param $rules
     * @param $newRule
     */
    protected function appendRule(&$rules, $newRule)
    {
        /**
         * $inspections and $newInspection can be one of the following:
         * string, e.g. `string|max:255`
         * array, e.g. `['string', 'max:255']`
         * object, e.g. `Rule::in(['Admin', 'User', 'Guest'])`
         *
         * so we have to account for all scenarios.  if one of them isn't, we ignore it
         */
        if (is_string($newRule)) {

            if (is_string($rules)) {
                $rules .= '|' . $newRule;
            } elseif (is_array($rules)) {
                $newRule = explode('|', $newRule);
                $rules = array_merge($rules, $newRule);
            } elseif (is_object($rules)) {
                $newRule = explode('|', $newRule);
                $rules = array_merge([$rules], $newRule);
            }

        } elseif (is_array($newRule)) {

            if (is_string($rules)) {
                $rules = explode('|', $rules);
                $rules = array_merge($rules, $newRule);
            } elseif (is_array($rules)) {
                $rules = array_merge($rules, $newRule);
            } elseif (is_object($rules)) {
                $rules = array_merge([$rules], $newRule);
            }

        } elseif (is_object($newRule)) {

            if (is_string($rules)) {
                $rules = explode('|', $rules);
                $rules[] = $newRule;
            } elseif (is_array($rules)) {
                $rules[] = $newRule;
            } elseif (is_object($rules)) {
                $rules = [$rules, $newRule];
            }

        }
    }

    /**
     * @param array $contextRules
     * @param $contextualTransformations
     * @return array
     */
    protected function applyTransformations(array $contextRules, $contextualTransformations)
    {
        foreach ($contextualTransformations as $rules) {
            foreach ($rules as $transformation => $parameters) {
                switch ($transformation) {
                    case 'append':
                        if (isset($contextRules[$parameters[0]])) {
                            $this->appendRule($contextRules[$parameters[0]], $parameters[1]);
                        }
                        break;
                    case 'appendAll':
                        foreach ($contextRules as $field => &$rules) {
                            $this->appendRule($rules, $parameters);
                        }
                        break;
                    case 'remove':
                        foreach ($parameters as $parameter) {
                            unset($contextRules[$parameter]);
                        }
                        break;
                    case 'replace':
                        if (isset($contextRules[$parameters[0]])) {
                            $contextRules[$parameters[0]] = $parameters[1];
                        }
                        break;
                    case 'requireAll':
                        foreach ($contextRules as $field => &$rules) {
                            $this->appendRule($rules, 'required');
                        }
                        break;
                }
            }
        }

        return $contextRules;
    }
}