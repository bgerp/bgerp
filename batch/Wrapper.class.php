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
 * @copyright 2006 - 2016 Experta OOD
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
    	$this->TAB('batch_Items', 'Наличности', 'ceo, batch');
    	
    	if(haveRole('debug')){
    		$this->TAB('batch_Movements', 'Движения->Журнал', 'ceo, batch');
    		$this->TAB('batch_BatchesInDocuments', 'Движения->Чернови', 'debug');
    	} else {
    		$this->TAB('batch_Movements', 'Движения', 'ceo, batch');
    	}
    	
    	$this->TAB('batch_Defs', 'Партиди->Артикули', 'ceo, batchMaster');
    	$this->TAB('batch_Templates', 'Партиди->Видове', 'ceo, batchMaster');
    	$this->TAB('batch_Features', 'Свойства', 'debug');
    	
    	$this->title = 'Партиди';
    }
}
