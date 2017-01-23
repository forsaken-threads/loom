<?php

function emptyish($variable)
{
    if ($variable === 0 || $variable === '0' || $variable === (float) 0) {
        return false;
    }
    return empty($variable);
}

function shallow($variable)
{
    if (emptyish($variable)) {
        return true;
    }
    if (is_array($variable)) {
        $variable = array_dot($variable);
        return emptyish(implode($variable));
    }
    return false;
}