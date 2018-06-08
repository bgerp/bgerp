<?php



/**
 * Клас 'lab_Wrapper'
 *
 *
 * @category  bgerp
 * @package   lab
 * @author    Milen Georgiev <milen@download.bg>
 *            Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class lab_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        
        
        $this->TAB('lab_Tests', 'Тестове', 'lab,ceo');
        $this->TAB('lab_Methods', 'Методи', 'lab,ceo');
        $this->TAB('lab_Parameters', 'Параметри', 'lab,ceo');
        
        $this->title = 'Лаборатория';
    }
}