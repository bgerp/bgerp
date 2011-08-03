<?php

/**
 * Менаджира детайлите на методите на плащане (Details)
 */
class common_PaymentMethodDetails extends core_Detail
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Детайли на начини на плащане";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = "Начини на плащане";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools,
                     plg_Printing, common_Wrapper, plg_Sorting,
                     PaymentMethods=common_PaymentMethods';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $masterKey = 'paymentMethodId';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'num=№, base, days, round, rate, tools=Ред';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $tabName = "common_PaymentMethods";
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('paymentMethodId', 'key(mvc=common_PaymentMethods)', 'caption=Начин на плащане, input=hidden, silent');
        $this->FLD('base', 'enum(beforeOrderDate=Преди датата на договора, 
                                 afterOrderDate=След датата на договора,
                                 beforeTransferDate=Преди датата на предаване на стоката,
                                 afterTransferDate=След датата на предаване на стоката)', 'caption=Спрямо, notSorting');
        $this->FLD('days', 'int(min=0)', 'caption=Дни, notSorting');
        $this->FLD('round', 'enum(no=Няма,eom=До края на месеца,eow=До края на седмицата)', 'caption=Закръгляне период, notSorting');
        $this->FLD('rate', 'int', 'caption=Процент от цялата сума, notSorting');
    }
    
    
    /**
     * Prepare 'num' and 'rate'
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // Prpare 'Num'
        static $num;
        $num += 1;
        $row->num = $num;
        
        $row->rate .= " %";
    }
    
    
    /**
     * round = 'no' по default
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
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
    function on_AfterSave($mvc, &$id, $rec)
    {
        $mvc->Master->invoke('afterDetailChanged', array($res, $mvc, $rec->paymentMethodId, 'edit', array($id)));
    }
}