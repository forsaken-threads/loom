<?php

function nonZeroEmpty($variable)
{
    if ($variable === 0 || $variable === '0' || $variable === (float) 0) {
        return false;
    }
    return empty($variable);
}

function shallow($variable)
{
    if (nonZeroEmpty($variable)) {
        return true;
    }
    if (is_array($variable)) {
        $variable = array_dot($variable);
        return nonZeroEmpty(implode($variable));
    }
    return false;
}