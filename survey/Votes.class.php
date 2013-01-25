<?php



/**
 * Модел "Гласуване"
 *
 *
 * @category  bgerp
 * @package   survey
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class survey_Votes extends core_Manager {
    
    
    /**
     * Заглавие
     */
    var $title = 'Гласуване';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, survey_Wrapper, plg_Sorting, plg_Search';
    
  
    /**
     * Кои полета да се показват в листовия изглед
     */
    //var $listFields = 'id, iban, contragent=Контрагент, currencyId, type';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Гласуване";
    
    
    /**
     * Икона на единичния обект
     */
    //var $singleIcon = 'img/16/money.png';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    //var $rowToolsSingleField = 'iban';

    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'survey, ceo, admin';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'survey, ceo, admin';
    
    
    /**
	 * Файл за единичен изглед
	 */
	//var $singleLayoutFile = 'survey/tpl/SingleAccountLayout.shtml';
	
	
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('alternativeId', 'key(mvc=survey_Alternatives)', 'caption=Въпрос, input=hidden, silent');
    	$this->FLD('rate', 'int', 'caption=Отговор');
    	$this->FLD('userUid', 'varchar(32)', 'caption=Потребител');
    }
}