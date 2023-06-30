<?php


/**
 * Интерфейс за работа с плъгина за autocomplete
 *
 * @category  vendors
 * @package   rtac
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rtac_AutocompleteIntf
{
    /**
     * Добавя необходимите неща за да работи плъгина
     *
     * @param core_Et $tpl
     */
    public function loadPacks(&$tpl)
    {
        return $this->class->loadPacks($tpl);
    }
    
    
    /**
     * Стартира autocomplete-а за добавяне на потребители
     *
     * @param core_Et $tpl
     * @param string  $rtId
     * @param integer  $maxShowCnt
     */
    public function runAutocompleteUsers(&$tpl, $rtId, $maxShowCnt)
    {
        return $this->class->runAutocompleteUsers($tpl, $rtId, $maxShowCnt);
    }
    
    
    /**
     * Стартира autocomplete-а за добавяне на текст
     *
     * @param core_Et $tpl
     * @param string  $rtId
     * @param integer  $maxShowCnt
     */
    public function runAutocompleteText(&$tpl, $rtId, $maxShowCnt)
    {
        return $this->class->runAutocompleteText($tpl, $rtId, $maxShowCnt);
    }
}
