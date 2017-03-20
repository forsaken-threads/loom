<?php

namespace App\Loom;

use App\Contracts\QualityControlContract;
use App\Exceptions\QualityControlException;

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
    protected $messages = [];

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
     * @return $this
     */
    public function append($fields, $rule)
    {
        if (!is_array($fields)) {
            $fields = (array) $fields;
        }
        foreach ($fields as $field) {
            $this->contextualTransformations[$this->editingContext][] = ['append' => [$field, $rule]];
        }
        return $this;
    }

    /**
     * @param $rules
     * @return $this
     */
    public function appendAll($rules)
    {
        $this->contextualTransformations[$this->editingContext][] = ['appendAll' => $rules];
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
        return $this;
    }

    /**
     * @return array
     */
    public function getConnectableResources()
    {
        return $this->connectableResources;
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
     * @param null $context
     * @return Inspections
     * @throws QualityControlException
     */
    public function getRules($context = null)
    {
        if (empty($context)) {
            $context = '__default';
        }
        $rules = $this->applyTransformations($context);
        if ($collisions = array_intersect(array_keys($rules), $this->connectableResources)) {
            throw new QualityControlException(trans('quality-control.filterable.property-connectable-resource-collision', ['collisions' => implode(',', $collisions)]));
        }
        return new Inspections($rules);
    }

    /**
     * @param $fields
     * @return $this
     */
    public function remove($fields)
    {
        if (!is_array($fields)) {
            $fields = (array) $fields;
        }
        $this->contextualTransformations[$this->editingContext][] = ['remove' => $fields];
        return $this;
    }
    /**
     * @param $fields
     * @param $rule
     * @return $this
     */
    public function replace($fields, $rule)
    {
        if (!is_array($fields)) {
            $fields = (array) $fields;
        }
        foreach ($fields as $field) {
            $this->contextualTransformations[$this->editingContext][] = ['replace' => [$field, $rule]];
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function requireAll()
    {
        $this->contextualTransformations[$this->editingContext][] = ['requireAll' => true];
        return $this;
    }

    /**
     * @param $fields
     * @return $this
     */
    public function requireExcept($fields)
    {
        if (!is_array($fields)) {
            $fields = (array) $fields;
        }
        $this->append(array_diff(array_keys($this->defaultRules), $fields), 'required');
        return $this;
    }

    /**
     * @param $fields
     * @return $this
     */
    public function requireOnly($fields)
    {
        if (!is_array($fields)) {
            $fields = (array) $fields;
        }
        $this->append($fields, 'required');
        return $this;
    }

    /**
     * @param array $connectableResources
     * @return $this
     */
    public function setConnectableResources($connectableResources)
    {
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
     * @param $context
     * @return array
     */
    protected function applyTransformations($context)
    {
        $contextRules = $this->defaultRules;

        if (empty($this->contextualTransformations[$context])) {
            return $contextRules;
        }

        foreach ($this->contextualTransformations[$context] as $rules) {
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