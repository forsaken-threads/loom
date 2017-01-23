<?php

namespace App\Traits;

trait ValidatesContextually
{
    abstract public function getValidationRules();

    public function getValidationRulesForContext($context = '__default', $allRulesTogether = true, $input = [])
    {
        $validations = $this->getValidationRules();
        if (!isset($validations['__default'])) {
            throw new ValidationRulesDefaultNotFoundException($this->getNiceName() . ' does not have any default validation rules defined.');
        }
        $validations = $this->validation_rules['default'];
        if (isset($this->validation_rules[$context])) {
            foreach ($this->validation_rules[$context] as $property => $rule) {
                if (strpos($property, '__') === 0) {
                    continue;
                }
                if (!isset($validations[$property])) {
                    $validations[$property] = '';
                }
                if (!is_array($rule)) {
                    $validations[$property] .= "|$rule";
                } else {
                    $new_rule = $validations[$property];
                    foreach ($rule as $command => $value) {
                        switch ($command) {
                            case 'append':
                                $new_rule .= "|$value";
                                break;
                            case 'replace':
                                $new_rule = $value;
                                break;
                            case 'substitute_property':
                                list($pattern, $substitute_property) = explode('|', $value);
                                $new_rule = str_replace($pattern, $this->$substitute_property, $new_rule);
                                break;
                        }
                    }
                    $validations[$property] = $new_rule;
                }
            }
            $this->applyBlanketValidationTransformations($this->validation_rules[$context], $validations, $input, $context);
        }
        $this->applyBlanketValidationTransformations($this->validation_rules, $validations, $input, $context);
        if ($allRulesTogether) {
            return $validations;
        }
        $sometimes_rules = [];
        $rules = [];
        foreach ($validations as $property => $validation_rules) {
            if (!is_array($validation_rules)) {
                $validation_rules = explode('|', $validation_rules);
            }
            foreach ($validation_rules as $value) {
                $parts = explode(':', $value, 2);
                $command = $parts[0];
                $parameters = isset($parts[1]) ? $parts[1] : '';
                if ($this->isSometimesRule($command)) {
                    $sometimes_rules[$property][] = $command . (strlen($parameters) ? ':' . $parameters : '');
                } else {
                    $rules[$property][] = $command . (strlen($parameters) ? ':' . $parameters : '');
                }
            }
        }
        $messages = !empty($this->validation_rules['__messages']) ? array_dot($this->validation_rules['__messages']) : [];
        return [$rules, $sometimes_rules, $messages];
    }

}