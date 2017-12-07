<?php



/**
 * Клас 'trans_Cmr'
 *
 * Документ за Транспортни линии
 *
 *
 * @category  bgerp
 * @package   trans
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class trans_Cmrs extends core_Master
{
	
    /**
     * Заглавие
     */
    public $title = 'Товарителници';


    /**
     * Абревиатура
     */
    public $abbr = 'CMR';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, trans_Wrapper, plg_Printing, plg_Clone,doc_DocumentPlg, plg_Search, doc_ActivatePlg, doc_EmailCreatePlg';

    
    /**
     * По кои полета ще се търси
     */
    //public $searchFields = 'title, vehicleId, forwarderId, forwarderPersonId, id';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, trans';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, trans';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, trans';


    /**
     * Кой има право да пише?
     */
    public $canWrite = 'ceo, trans';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    //public $listFields = 'id, handler=Документ, title, start, folderId, createdOn, createdBy';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Товарителница';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'trans/tpl/SingleLayoutCMR.shtml';
    		
    		
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/lorry_go.png';
    
   
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.7|Логистика";
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('senderData', 'text(rows=2)', 'caption=1. Изпращач');
    	$this->FLD('consigneeData', 'text(rows=2)', 'caption=2. Получател');
    	
    	$this->FLD('deliveryPlace', 'text(rows=2)', 'caption=3. Разтоварен пункт');
    	$this->FLD('takingOverPlace', 'text(rows=2)', 'caption=4. Товарен пункт');
    	$this->FLD('takingDate', 'date', 'caption=4. Дата на товарене');
    	$this->FLD('documentsAttached', 'varchar', 'caption=5. Приложени документи');
    	
    	$this->FLD('mark1', 'varchar', 'caption=1. Информация за стоката->6. Знаци и Номера');
    	$this->FLD('numOfPacks1', 'varchar', 'caption=1. Информация за стоката->7. Брой колети');
    	$this->FLD('methodOfPacking1', 'varchar', 'caption=1. Информация за стоката->8. Вид опаковка');
    	$this->FLD('natureOfGoods1', 'varchar', 'caption=1. Информация за стоката->9. Вид стока');
    	$this->FLD('statNum1', 'varchar', 'caption=1. Информация за стоката->10. Статистически №');
    	$this->FLD('grossWeight1', 'varchar', 'caption=1. Информация за стоката->11. Тегло Бруто');
    	$this->FLD('volume1', 'varchar', 'caption=1. Информация за стоката->12. Обем');
    	
    	$this->FLD('mark2', 'varchar', 'caption=2. Информация за стоката->6. Знаци и Номера,autohide');
    	$this->FLD('numOfPacks2', 'varchar', 'caption=2. Информация за стоката->7. Брой колети,autohide');
    	$this->FLD('methodOfPacking2', 'varchar', 'caption=2. Информация за стоката->8. Вид опаковка,autohide');
    	$this->FLD('natureOfGoods2', 'varchar', 'caption=2. Информация за стоката->9. Вид стока,autohide');
    	$this->FLD('statNum2', 'varchar', 'caption=2. Информация за стоката->10. Статистически №,autohide');
    	$this->FLD('grossWeight2', 'varchar', 'caption=2. Информация за стоката->11. Тегло Бруто,autohide');
    	$this->FLD('volume2', 'varchar', 'caption=2. Информация за стоката->12. Обем,autohide');
    	
    	$this->FLD('mark3', 'varchar', 'caption=3. Информация за стоката->6. Знаци и Номера,autohide');
    	$this->FLD('numOfPacks3', 'varchar', 'caption=3. Информация за стоката->7. Брой колети,autohide');
    	$this->FLD('methodOfPacking3', 'varchar', 'caption=3. Информация за стоката->8. Вид опаковка,autohide');
    	$this->FLD('natureOfGoods3', 'varchar', 'caption=3. Информация за стоката->9. Вид стока,autohide');
    	$this->FLD('statNum3', 'varchar', 'caption=3. Информация за стоката->10. Статистически №,autohide');
    	$this->FLD('grossWeight3', 'varchar', 'caption=3. Информация за стоката->11. Тегло Бруто,autohide');
    	$this->FLD('volume3', 'varchar', 'caption=3. Информация за стоката->12. Обем,autohide');
    	
    	$this->FLD('mark4', 'varchar', 'caption=4. Информация за стоката->6. Знаци и Номера,autohide');
    	$this->FLD('numOfPacks4', 'varchar', 'caption=4. Информация за стоката->7. Брой колети,autohide');
    	$this->FLD('methodOfPacking4', 'varchar', 'caption=4. Информация за стоката->8. Вид опаковка,autohide');
    	$this->FLD('natureOfGoods4', 'varchar', 'caption=4. Информация за стоката->9. Вид стока,autohide');
    	$this->FLD('statNum4', 'varchar', 'caption=4. Информация за стоката->10. Стат. №,autohide');
    	$this->FLD('grossWeight4', 'varchar', 'caption=4. Информация за стоката->11. Тегло Бруто,autohide');
    	$this->FLD('volume4', 'varchar', 'caption=4. Информация за стоката->12. Обем,autohide');
    	
    	$this->FLD('class', 'varchar', 'caption=ADR->Клас');
    	$this->FLD('number', 'double', 'caption=ADR->Цифра');
    	$this->FLD('letter', 'varchar', 'caption=ADR->Буква');
    	
    	$this->FLD('senderInstructions', 'text(rows=2)', 'caption=Допълнително->13. Указания на изпращача');
    	$this->FLD('instructionsPayment', 'text(rows=2)', 'caption=Допълнително->14. Предп. плащане навло');
    	
    	$this->FLD('cashOnDelivery', 'varchar', 'caption=Допълнително->15. Наложен платеж');
    	$this->FLD('cariersData', 'text(rows=2)', 'caption=Допълнително->16. Превозвач');
    	$this->FLD('vehicleReg', 'varchar', 'caption=МПС регистрационен №');
    	$this->FLD('successiveCarriers', 'text(rows=2)', 'caption=Допълнително->17. Посл. превозвачи');
    	$this->FLD('specialagreements', 'text(rows=2)', 'caption=Допълнително->19. Спец. споразумения');
    }
}