<?php



/**
 * Клас 'acc_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'Acc'
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class php_Wrapper extends bgerp_ProtoWrapper
{

    /**
     * Описание на табовете
     */
    function description()
    {
        
        
        $this->TAB('php_Formater', 'Форматер');
        $this->TAB('php_Const', 'Константи');
        $this->TAB('php_Interfaces', 'Интерфейси');
        $this->TAB('php_Test', 'Тест система');
        
        $this->title = 'Програма за форматиране на кода';
    }
    
}