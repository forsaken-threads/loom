<?php

namespace App\Traits;

use App\Loom\FilterScope;
use App\Loom\QualityControl;

trait QualityControllable
{
    use Filterable,
        Sortable;

    /**
     * Get the Quality Control object for this class
     *
     * @return QualityControl
     */
    abstract public function getQualityControl();

    /**
     * @param string $context
     * @param bool $allRulesTogether
     * @return array
     */
    public function getValidationRulesForContext($context = '__default', $allRulesTogether = true)
    {
        $qc = $this->getQualityControl();

        if ($allRulesTogether) {
            return $qc->getRules($context);
        }

        $dependentRules = [];
        $independentRules = [];
        foreach ($qc->getRules($context) as $property => $rules) {
            // These are the Illuminate\Validation\Rule based rules and are not dependent rules
            if (is_object($rules)) {
                continue;
            }

            // Convert string-based rules to an array so we can handle them all the same
            if (is_string($rules)) {
                $rules = explode('|', $rules);
            }
            foreach ($rules as $instruction) {
                $instructions = explode(':', $instruction, 2);
                $rule = $instructions[0];
                $parameters = isset($instructions[1]) ? $instructions[1] : '';
                if ($this->isDependentRule($rule)) {
                    $dependentRules[$property][] = $rule . (strlen($parameters) ? ':' . $parameters : '');
                } else {
                    $independentRules[$property][] = $rule . (strlen($parameters) ? ':' . $parameters : '');
                }
            }
        }

        return [$independentRules, $dependentRules, $qc->getMessages()];
    }

    /**
     * @return array
     */
    public function getFilterValidationRules()
    {
        return $this->getValidationRulesForContext('filter');
    }

    /**
     * @param $scopeName
     * @return FilterScope
     */
    public function getFilterScope($scopeName)
    {
        return $this->getQualityControl()->getFilterScope($scopeName);
    }

    /**
     * @return array
     */
    public function getSortableProperties()
    {
        return array_keys($this->getValidationRulesForContext('sort'));
    }
    /**
     * @param $rule
     * @return bool
     */
    public function isDependentRule($rule)
    {
        return false;
    }

}