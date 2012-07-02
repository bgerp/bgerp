<?php



/**
 * Опаковка на пакета `accda`
 *
 * Поддържа системното меню и табове-те на пакета 'Acc'
 *
 *
 * @category  bgerp
 * @package   accda
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class accda_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
      
        
        $this->TAB('accda_Da', 'Инвентарна книга', 'admin,accda');
        $this->TAB('accda_Groups', 'Групи', 'admin,accda');
        $this->TAB('accda_Documents', 'Документи', 'admin,accda');
      
        
        $this->title = 'ДА « Счетоводство';
        Mode::set('menuPage', 'Счетоводство:ДА');
    }
}