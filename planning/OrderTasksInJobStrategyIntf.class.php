<?php


/**
 * Интерфейс за стратегии за подреждане на операциите в рамките на заданието
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за стратегии за подреждане на операциите в рамките на заданието
 */
class planning_OrderTasksInJobStrategyIntf
{


    /**
     * Преподреждане на операциите в рамките на задание
     *
     * @param array $taskArr
     *          <ид на операция> => array(ид-та на предходни операции)
     * @return array
     */
    public function order($taskArr)
    {
        return $this->class->order($taskArr);
    }
}