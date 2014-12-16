<?php


/**
 * Клас 'mp_ProductionNormDetails'
 *
 * Детайли на мениджър на детайлите на протокола за производство
 *
 * @category  bgerp
 * @package   mp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class mp_ProductionNoteDetails extends deals_ManifactureDetail
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Детайли на протокола от производство';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Продукт';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'noteId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Created, mp_Wrapper, plg_RowNumbering, plg_AlignDecimals';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, mp';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, mp';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, mp';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, mp';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, measureId, quantity, selfValue, amount';
    
        
    /**
     * Активен таб
     */
    public $currentTab = 'Протоколи->Производство';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('noteId', 'key(mvc=mp_ProductionNotes)', 'column=none,notNull,silent,hidden,mandatory');
        
        parent::setDetailFields($this);
        
        $this->FLD('selfValue', 'double', 'caption=С-ст,mandatory');
        $this->FNC('amount', 'double', 'caption=Сума');
        
        $this->setDbUnique('noteId,productId,classId');
    }
    
    
    /**
     * Изчисляване на сумата на реда
     */
    public static function on_CalcAmount($mvc, $rec)
    {
    	if(empty($rec->quantity) || empty($rec->selfValue)) return;
    	
    	$rec->amount = $rec->quantity * $rec->selfValue;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
    	if($form->rec->productId){
    		
    		//@TODO да проверява имали цена от технологичната карта, ако има да не се показва полето
    		
    		$masterValior = $mvc->Master->fetchField($form->rec->noteId, 'valior');
    		
    		$form->setField('selfValue', "unit=" . acc_Periods::getBaseCurrencyCode($masterValior));
    	}
    }
}
