<?php


/**
 * Токени за плащане чрез ePay.bg
 *
 * @category  bgerp
 * @package   epay
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class epay_Tokens extends core_Manager
{
    

    /**
     * Заглавие на мениджъра
     */
    public $title = 'Токени за плащане чрез ePay.bg';
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_Created';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Токен за плащане чрез ePay.bg';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да го редактира?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'initiatorId=Източник,currencyId,token,paymentId,createdOn=Създадено на';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('token', 'varchar', 'caption=Токен');
        $this->FLD('paymentId', 'key(mvc=cond_PaymentMethods,select=title,allowEmpty)', 'caption=Плащане');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Валута');
        $this->FLD('initiatorClassId', 'class', 'caption=Инициатор->Клас');
        $this->FLD('initiatorId', 'int', 'caption=Инициатор->Ид');
        
        $this->setDbIndex('initiatorClassId,initiatorId,currencyId');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->initiatorId = cls::get('initiatorClassId')->getHyperlink($rec->initiatorId, true);
    }
    
    
    public static function force($initiatorClass, $initiatorId, $currencyCode)
    {
        bp();
    }
}