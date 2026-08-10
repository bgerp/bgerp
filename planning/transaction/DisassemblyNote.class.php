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

        $result = (object) array(
            'reason'      => 'Протокол за разпад №' . ($rec->id ?? ''),
            'valior'      => $rec->valior,
            'totalAmount' => null,
            'entries'     => array(),
        );

        if (isset($rec->id)) {
            $result->entries = $this->getEntries($rec);
        }

        return $result;
    }


    /**
     * Връща записите на транзакцията
     */
    private function getEntries($rec)
    {
        $entries = $productionRecs = array();
        $inputRec = null;

        $dQuery = planning_DisassemblyNoteDetails::getQuery();
        $dQuery->where("#noteId = {$rec->id}");
        $dQuery->where('#quantity != 0');
        $dQuery->orderBy('id', 'ASC');
        while ($dRec = $dQuery->fetch()) {
            if ($dRec->type == 'input') {
                $inputRec = $dRec;
            } else {
                $productionRecs[] = $dRec;
            }
        }

        if (!$inputRec || !countR($productionRecs)) {
            return $entries;
        }

        $documentItem = array($this->class->getClassId(), $rec->id);
        $inputProductItem = array('cat_Products', $inputRec->productId);

        // Отписване на артикула за разпад по стратегията на 321
        $entries[] = array(
            'debit' => array('61103', $documentItem, $inputProductItem, 'quantity' => $inputRec->quantity),
            'credit' => array('321', array('store_Stores', $inputRec->storeId), $inputProductItem, 'quantity' => $inputRec->quantity),
            'reason' => 'Влагане на артикула за разпад',
        );

        // Прогнозната стойност на вложения артикул - по нея се смятат режийните
        if (isset($rec->expenses)) {
            $inputAmount = cat_Products::getWacAmountInStore($inputRec->quantity, $inputRec->productId, $rec->valior, $inputRec->storeId);
            $expensesAmount = round($inputAmount * $rec->expenses, 2);

            // Както при директния протокол за производство
            if ($expensesAmount <= 0) {
                $expensesAmount = 0;
            }

            $entries[] = array(
                'amount' => $expensesAmount,
                'debit' => array('61103', $documentItem, $inputProductItem, 'quantity' => 0),
                'credit' => array('61102'),
                'reason' => 'Разпределени режийни разходи',
            );
        }

        // По процент на ред, към ВАЛЬОРА - за да даде реконтирането същия резултат
        $statuses = array();
        $percentsArr = cat_plg_DisassemblyDoc::getPercents($this->class, $rec, $rec->valior, $statuses);
        $isSaving = Mode::is('saveTransaction') || Mode::is('recontoTransaction');
        if ($isSaving) {
            $errorMsg = planning_DisassemblyNote::getPercentsError($rec);
            acc_journal_RejectRedirect::expect(!isset($errorMsg), $errorMsg);
        }

        $allocatedInputQuantity = 0;
        $lastIndex = countR($productionRecs) - 1;
        $inputUomRec = cat_UoM::fetch($inputRec->packagingId, 'round,roundSignificant');

        foreach ($productionRecs as $index => $dRec) {
            $percent = $percentsArr[$dRec->id]->percent ?? 0;

            // Снимка на процента, с който се контира - пише се и при реконтиране.
            // Не заопашва updateMaster, иначе самото контиране би реконтирало
            // документа веднага след себе си (@see core_Detail::save_)
            if ($isSaving) {
                $dRec->contoPercent = $percent;
                $dRec->_skipDetailRevision = true;
                Mode::push("stopMasterUpdate{$rec->id}", true);
                planning_DisassemblyNoteDetails::save($dRec, 'contoPercent');
                Mode::pop("stopMasterUpdate{$rec->id}");
            }

            // Последният ред обира остатъка, за да няма разлика от закръгляне.
            $inputQuantityPart = ($index == $lastIndex)
                ? $inputRec->quantity - $allocatedInputQuantity
                : core_Math::roundNumber($inputRec->quantity * $percent, $inputUomRec->round, $inputUomRec->roundSignificant);
            $allocatedInputQuantity += $inputQuantityPart;

            $productRec = cat_Products::fetch($dRec->productId, 'canStore,fixedAsset');
            if ($productRec->canStore == 'yes') {
                $debit = array(
                    '321',
                    array('store_Stores', $dRec->storeId),
                    array('cat_Products', $dRec->productId),
                    'quantity' => $dRec->quantity,
                );
                $reason = 'Заприхождаване на артикул от разпад';
            } else {
                if ($productRec->fixedAsset == 'yes') {
                    $expenseItem = array('cat_Products', $dRec->productId);
                } elseif (acc_Items::isItemInList($this->class, $rec->id, 'costObjects')) {
                    $expenseItem = array($this->class->getClassId(), $rec->id);
                } else {
                    $expenseItem = acc_Items::forceSystemItem(
                        'Неразпределени разходи',
                        'unallocated',
                        'costObjects'
                    )->id;
                }

                $debit = array(
                    '60201',
                    $expenseItem,
                    array('cat_Products', $dRec->productId),
                    'quantity' => $dRec->quantity,
                );
                $reason = 'Произвеждане на услуга от разпад';
            }

            $entries[] = array(
                'debit' => $debit,
                'credit' => array('61103', $documentItem, $inputProductItem, 'quantity' => $inputQuantityPart),
                'reason' => $reason,
            );
        }

        return $entries;
    }
}
