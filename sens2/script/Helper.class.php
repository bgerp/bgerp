<?php

/**
 *
 */


class sens2_script_Helper {

    public static function getSuggestions($scriptId)
    {
        $vars = sens2_script_DefinedVars::getContex($scriptId);
        foreach ($vars as $i => $v) {
            $suggestions[$i] = $i;
        }
        $inds = sens2_Indicators::getContex($scriptId);
        foreach ($inds as $i => $v) {
            $suggestions[$i] = $i;
        }
        asort($suggestions);

        return $suggestions;
    }
}