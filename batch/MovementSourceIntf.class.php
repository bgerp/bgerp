<?php



/**
 * Интерфейс за документ генериращ партидни движения
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class batch_MovementSourceIntf
{

    /**
     * Инстанция на мениджъра имащ интерфейса
     */
    public $class;
    
    
    /**
     * Връща масива с партидните движения, които поражда документа,
     * Ако никой от артикулите няма партида връща празен масив
     *
     * @param  mixed $id - ид или запис
     * @return array $res - движенията
     *                  o int productId         - ид на артикула
     *                  o int storeId           - ид на склада
     *                  o varchar batch         - номера на партидата
     *                  o double quantity       - количеството
     *                  o in|out|stay operation - операция (влиза,излиза,стои)
     *                  o date date             - дата на операцията
     */
    public function getMovements($id)
    {
        return $this->class->getMovements($id);
    }
}
