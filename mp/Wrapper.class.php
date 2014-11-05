<?php



/**
 * Планиране - опаковка
 *
 *
 * @category  bgerp
 * @package   mp
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class mp_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        
        $this->TAB('mp_Jobs', 'Задания', 'ceo,mp');
        
        $this->TAB(array('mp_Resources', 'list', 'type' => 'equipment'), 'Ресурси->Оборудване', 'ceo,mp');
        $this->TAB(array('mp_Resources', 'list', 'type' => 'material'), 'Ресурси->Материал', 'ceo,mp');
        $this->TAB(array('mp_Resources', 'list', 'type' => 'labor'), 'Ресурси->Труд', 'ceo,mp');
        $this->TAB('mp_ObjectResources', 'Ресурси->Отношения', 'ceo,debug');
        
        $this->TAB('mp_Tasks', 'Задачи', 'ceo,mp');
         
        $this->title = 'Планиране';
        
    }
}