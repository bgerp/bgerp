<?php



/**
 * Мениджира детайлите на методите на плащане (Details)
 *
 *
 * @category  all
 * @package   bank
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_PaymentMethodDetails extends core_Detail
{
    
    
    /**
     * Заглавие
     */
    var $title = "Детайли на начини на плащане";
    
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Начини на плащане";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools,
                     plg_Printing, bank_Wrapper, plg_Sorting,
                     PaymentMethods=bank_PaymentMethods';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'paymentMethodId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'num=№, base, days, round, rate, tools=Ред';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Активния таб в случай, че wrapper-а е таб контрол.
     */
    var $tabName = "bank_PaymentMethods";
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('paymentMethodId', 'key(mvc=bank_PaymentMethods)', 'caption=Начин на плащане, input=hidden, silent');
        $this->FLD('base', 'enum(beforeOrderDate=Преди датата на договора, 
                                 afterOrderDate=След датата на договора,
                                 beforeTransferDate=Преди датата на предаване на стоката,
                                 afterTransferDate=След датата на предаване на стоката)', 'caption=Спрямо, notSorting');
        $this->FLD('days', 'int(min=0)', 'caption=Дни, notSorting');
        $this->FLD('round', 'enum(no=Няма,eom=До края на месеца,eow=До края на седмицата)', 'caption=Закръгляне период, notSorting');
        $this->FLD('rate', 'int', 'caption=% от сумата, notSorting, unit=%');
    }
    
    
    /**
     * Prepare 'num' and 'rate'
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // Prpare 'Num'
        static $num;
        $num += 1;
        $row->num = $num;
    }
    
    
    /**
     * round = 'no' по default
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        if (!$data->form->rec->id) {
            $data->form->setDefault('round', 'no');
        }
    }
    
    
    /**
     * Проверка за 100% от плащането като сбор от всички вноски
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @param stdClass $rec
     */
    static function on_AfterSave($mvc, $id, $rec)
    {
        $mvc->Master->invoke('afterDetailChanged', array($res, $mvc, $rec->paymentMethodId, 'edit', array($id)));
    }
}