<?php



/**
 * Плъгин за Филтриране на документи с вальор по ключови думи и дата,
 * показва и Обобщение на резултатите от списъчния изглед 
 * @TODO
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_plg_DocumentSummary extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     * 
     * @param core_Mvc $mvc
     */
    function on_AfterDescription(core_Mvc $mvc)
    {
    	// Проверка за приложимост на плъгина към зададения $mvc
        static::checkApplicability($mvc);
        $plugins = arr::make($mvc->loadList);
        if(!isset($plugins['plg_Search'])){
        	$plugins[] = 'plg_Search';
        	$mvc->loadList = implode(',', $plugins);
        }
    }
    
    
    /**
     * Проверява дали този плъгин е приложим към зададен мениджър
     * 
     * @param core_Mvc $mvc
     * @return boolean
     */
    protected static function checkApplicability($mvc)
    {
    	// Прикачане е допустимо само към наследник на core_Manager ...
        if (!$mvc instanceof core_Manager) {
            return FALSE;
        }
      
        if(!$mvc->getInterface('acc_TransactionSourceIntf')) {
        	return FALSE;
        }
        
        return TRUE;
    }
    
    
    /**
	 *  Подготовка на филтър формата
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
	{
		$data->listFilter->view = 'horizontal';
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        $data->listFilter->FNC('from', 'date', 'width=6em,caption=От,silent');
		$data->listFilter->FNC('to', 'date', 'width=6em,caption=До,silent');
		$data->listFilter->setDefault('from', date('Y-m-01'));
		$data->listFilter->setDefault('to', date("Y-m-t", strtotime(dt::now())));
		$data->listFilter->showFields = 'search,from,to';
        
        // Активиране на филтъра
        $data->listFilter->input('search,from,to', 'silent');
	}
	
	
	/**
	 * Филтрираме резултатите
	 */
	function on_BeforePrepareListRecs($mvc, $res, $data)
	{
		if($filter = $data->listFilter->rec) {
			
			if($filter->search){
				plg_Search::applySearch($filter->search, $data->query);
			}
			
			if($filter->from) {
    			$data->query->where("#valior >= '{$filter->from}'");
    		}
    		
			if($filter->to) {
    			$data->query->where("#valior <= '{$filter->to} 23:59:59'");
    		}
		}
	}
}