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
class cal_TaskTypeIntf extends embed_DriverIntf
{
    
    
    /**
     * Връща подсказките за добавяне на прогрес
     * 
     * @param stdClass $tRec
     * 
     * @return array
     */
    public function getProgressSuggestions($tRec)
    {
        
        return $this->class->getProgressSuggestions($tRec);
    }
}
