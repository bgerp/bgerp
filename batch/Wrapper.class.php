<?php



/**
 * Клас 'batch_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'batch'
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class batch_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
    	$this->TAB('batch_Items', 'Партиди', 'ceo, batch');
     	$this->TAB('batch_Movements', 'Движения', 'ceo, batch');
     	$this->TAB('batch_Defs', 'Дефиниции', 'ceo, batch');
     	$this->TAB('batch_Features', 'Свойства', 'debug');
     	
        $this->title = 'Партиди';
    }
}