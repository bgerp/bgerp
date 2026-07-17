<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа planning_DisassemblyNote
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see acc_TransactionSourceIntf
 */
class planning_transaction_DisassemblyNote extends acc_DocumentTransactionSource
{
    /**
     * @param int $id
     *
     * @return stdClass
     *
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public function getTransaction($id)
    {
        expect($rec = $this->class->fetchRec($id));
        $rec->valior = empty($rec->valior) ? dt::today() : $rec->valior;

        // @todo Все още няма реална контировка на разпада - трябва да се обмисли
        //       как да се отпише артикулът за разпад (влагане) и как да се
        //       заприходят произведените от него артикули (аналогично на
        //       planning_transaction_ConsumptionNote/DirectProductionNote, но
        //       със себестойност, разпределена от разпадания артикул към
        //       произведените, не от рецепта).
        return (object) array(
            'reason'      => "Протокол за разпад №{$rec->id}",
            'valior'      => $rec->valior,
            'totalAmount' => null,
            'entries'     => array(),
        );
    }
}
