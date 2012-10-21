<?php



/**
 * Планиране - опаковка
 *
 *
 * @category  bgerp
 * @package   mp
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
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
        
        $this->TAB('mp_Jobs', 'Задания', 'admin,mp');

        $this->TAB('mp_Tasks', 'Задачи', 'admin,mp');
         
        $this->title = 'Планиране';
        
    }
}