<?php


/**
 * 
 * 
 * @category  bgerp
 * @package   cal
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cal_TaskType extends core_Mvc
{
    
    
    /**
     * 
     */
    public $interfaces = 'cal_TaskTypeIntf';
    
    
    /**
     * 
     */
    public $title = 'Задача';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        
    }
    
    
    /**
     * Може ли вградения обект да се избере
     * 
     * @param NULL|integer $userId
     * 
     * @return boolean
     */
    public function canSelectDriver($userId = NULL)
    {
        
        return TRUE;
    }
    
    
    /**
     * Връща подсказките за добавяне на прогрес
     * 
     * @param  stdClass $tRec
     * 
     * @return array
     */
    public function getProgressSuggestions($tRec)
    {
        $progressArr = array();
        for($i = 0; $i <= 100; $i += 10) {
            $p = $i . ' %';
            $progressArr[$p] = $p;
        }
        
        return $progressArr;
    }
}
