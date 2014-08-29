<?php



/**
 * Драйвър за експортиране на 'sales_Invoices' изходящи фактури към Bulmar office
 * 
 * 
 * @category  bgerp
 * @package   bulmar
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bulmar_InvoiceExport extends core_Manager {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'bgerp_ExportIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Експортиране на фактури към Bulmar Office";
    
    
    /**
     * Към кои мениджъри да се показва драйвъра
     */
    protected static $applyOnlyTo = 'sales_Invoices';
    
    
    /**
     * Мениджъри за зареждане
     */
    public $loadList = 'Invoices=sales_Invoices';
    
    
    /**
     * Подготвя формата за експорт
     * 
     * @param core_Form $form
     */
    function prepareExportForm(core_Form &$form)
    {
    	$form->FLD('from', 'date', 'caption=От,mandatory');
    	$form->FLD('to', 'date', 'caption=До,mandatory');
    }
    
    
    /**
     * Проверява импорт формата
     * 
     * @param core_Form $form
     */
    function checkExportForm(core_Form &$form)
    {
    	if($form->rec->from > $form->rec->to){
    		$form->setError('from,to', 'Началната дата трябва да е по-малка от голямата');
    	}
    }
    
    
    /**
	 * Инпортиране на csv-файл в даден мениджър
     * 
     * @param mixed $data - данни
     * @return mixed - експортираните данни
	 */
    function export($filter)
    {
    	$query = $this->Invoices->getQuery();
    	$query->where("#state = 'active'");
    	$query->between('date', $filter->from, $filter->to);
    	//$query->where("#date BETWEEN '{$filter->from}' AND '{$filter->to}'");
    	bp($query->buildQuery());
    }
    
    
    /**
     * Можели да се добавя към този мениджър
     */
    function isApplicable($mvc)
    {
    	return $mvc->className == self::$applyOnlyTo;
    }
}