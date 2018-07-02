<?php


/**
 * Интерфейс за бизнес информация, обобщена за всички документи по една сделка.
 *
 * Някои мениджъри (напр. "Продажба" - sales_Sales) могат да обобщят информацията от всички
 * документи по сделката, които реализират bgerp_DealIntf. Чрез този метод тази информация
 * става достъпна за другите.
 *
 * @category  bgerp
 *
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_DealAggregatorIntf
{
    /**
     * Генерира агрегираната бизнес информация за тази сделка.
     *
     * Обикаля всички документи, имащи отношение към бизнес информацията и извлича от всеки един
     * неговата "порция" бизнес информация. Всяка порция се натрупва към общия резултат до
     * момента.
     *
     * Списъка с въпросните документи, имащи отношение към бизнес информацията за продажбата е
     * сечението на следните множества:
     *
     *  Документите, върнати от @link doc_DocumentIntf::getDescendants()
     *  Документите, реализиращи интерфейса @link bgerp_DealIntf
     *  Документите, в състояние различно от `draft` и `rejected`
     *
     * @param int $id ид на документ
     *
     * @return bgerp_iface_DealAggregator
     */
    public function getAggregateDealInfo($id)
    {
        return $this->class->getAggregateDealInfo($id);
    }

    /**
     * Връща масив с кои платежни операции са позволени
     * Масив с елементи:.
     *
     * 		[operation_system_id]['title'] - име на операцията
     * 		['debit'] - systemId на дебитната сметка ('*' ако сметката се определя от документа)
     * 		['credit'] - systemId на кредит сметка ('*' ако сметката се определя от документа)
     * 		['reverse'] - TRUE/FALSE  дали да е обратна операция
     */
    public function getPaymentOperations($id)
    {
        return $this->class->getPaymentOperations($id);
    }

    /**
     * Връща масив с кои операции са позволени за експедиране/доставяне на услуги.
     */
    public function getShipmentOperations($id)
    {
        return $this->class->getShipmentOperations($id);
    }
}
