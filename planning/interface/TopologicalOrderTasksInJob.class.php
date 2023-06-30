<?php


/**
 * Имплементация на стратегия за топологично сортиране на подреждане на операциите в заданието
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Топологично сортиране на операциите в заданието
 */
class planning_interface_TopologicalOrderTasksInJob
{

    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'planning_OrderTasksInJobStrategyIntf';


    /**
     * Заглавие
     */
    public $title = 'Топологично сортиране на операциите в заданието';


    /**
     * Преподреждане на операциите в рамките на задание
     *
     * @param array $taskArr
     *          <ид на операция> => array(ид-та на предходни операции)
     * @return array
     */
    public function order($taskArr)
    {
        return planning_GraphSort::topologicalSort($taskArr);
    }
}