<?php

namespace App\Loom;

class QualityControl
{

    /**
     * @var array
     */
    protected $defaultRules;

    /**
     * @var array
     */
    protected $contextualTransformations = ['__default' => []];

    /**
     * @var string
     */
    protected $editingContext;

    /**
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
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param null $context
     * @return array
     */
    public function getRules($context = null)
    {
        if (empty($context)) {
            $context = '__default';
        }
        return $this->applyTransformations($context);
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
     * @param $messages
     * @return $this
     */
    public function withMessages($messages)
    {
        $this->messages = $messages;
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
        $baseRules = $this->defaultRules;

        if (empty($this->contextualTransformations[$context])) {
            return $baseRules;
        }

        foreach ($this->contextualTransformations[$context] as $rules) {
            foreach ($rules as $transformation => $parameters) {
                switch ($transformation) {
                    case 'append':
                        if (isset($baseRules[$parameters[0]])) {
                            $this->appendRule($baseRules[$parameters[0]], $parameters[1]);
                        }
                        break;
                    case 'appendAll':
                        foreach ($baseRules as $field => &$rules) {
                            $this->appendRule($rules, $parameters);
                        }
                        break;
                    case 'replace':
                        if (isset($baseRules[$parameters[0]])) {
                            $baseRules[$parameters[0]] = $parameters[1];
                        }
                        break;
                    case 'requireAll':
                        foreach ($baseRules as $field => &$rules) {
                            $this->appendRule($rules, 'required');
                        }
                        break;
                }
            }
        }

        return $baseRules;
    }
}