<?php


/**
 * Справка за документите, формиращи стойностите на продажба или покупка.
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Stefan Arsov <stefan.arsof@gmail.com> with Codex
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class deals_DocumentSummary
{
    /**
     * Събира сумите от документите и допълва липсващите по перото на сделката.
     */
    public static function prepare($deal, $rec)
    {
        $dealId = $rec->id ?? null;
        $threadId = $rec->threadId ?? null;
        $currency = $rec->currencyId ?? acc_Periods::getBaseCurrencyCode($rec->valior ?? null);
        $isSale = $deal instanceof sales_Sales;
        $combinedIds = keylist::toArray($rec->closedDocuments ?? null);
        $dealRecs = array($dealId => $rec);
        $groupIds = array();
        if ($combinedIds) {
            $query = $deal->getQuery();
            $query->in('id', $combinedIds);
            $dealRecs += $query->fetchAll();
            $groupIds = $combinedIds + array($dealId => $dealId);
        }
        $partial = (bool) array_diff_key($combinedIds, $dealRecs);

        $unpostedStates = array('draft', 'pending');
        $invoiceClasses = array('sales_Invoices', 'purchase_Invoices');
        $documentClasses = array(
            $deal->className, 'store_ShipmentOrders', 'store_Receipts', 'sales_Services', 'purchase_Services',
            'cash_Pko', 'cash_Rko', 'bank_IncomeDocuments', 'bank_SpendingDocuments',
            'findeals_DebitDocuments', 'findeals_CreditDocuments', 'acc_ValueCorrections', 'sales_ClosedDeals', 'purchase_ClosedDeals',
        );
        $threads = array_column($dealRecs, 'threadId');
        $containers = self::getThreadDocuments($threads, $invoiceClasses, array_merge(array('active'), $unpostedStates));
        $containers += self::getThreadDocuments($threads, $documentClasses, array_merge(array('active', 'closed'), $unpostedStates));
        $containersByThread = array();
        foreach ($containers as $container) {
            $containersByThread[$container->threadId ?? null][] = $container;
        }

        $rows = $threadDeals = array();
        foreach ($dealRecs as $sourceId => $source) {
            $sourceThread = $source->threadId ?? null;
            if ($groupIds && $sourceThread && $sourceThread != $threadId) {
                $threadDeals[$sourceThread] = array(
                    'handle' => '#' . $deal->getHandle($sourceId),
                    'url' => $deal->getSingleUrlArray($sourceId),
                );
            }

            $sourceRows = self::getDealRows($deal, $source, $containersByThread[$sourceThread] ?? array(), $groupIds, $currency, $isSale, $unpostedStates, $partial);
            foreach ($sourceRows as $key => $document) {
                $amounts = $document['amounts'];
                unset($document['amounts']);
                $rows[$key] ??= $document;
                foreach ($amounts as $field => $amount) {
                    // Фактурата има една сума, независимо по колко пера е намерена.
                    if ($field === 'invoiced' && isset($rows[$key][$field])) continue;

                    $rows[$key][$field] = ($rows[$key][$field] ?? 0) + $amount;
                }
            }
        }

        $totals = array('delivered' => 0, 'settled' => 0, 'invoiced' => 0);
        foreach ($rows as &$row) {
            $docThread = $row['threadId'];
            $row['otherThread'] = $docThread != $threadId;
            if ($groupIds && $docThread && $row['otherThread']) {
                if (!array_key_exists($docThread, $threadDeals)) {
                    $first = doc_Threads::getFirstDocument($docThread);
                    $threadDeals[$docThread] = $first && $first->isInstanceOf('deals_DealBase') && $first->haveRightFor('single')
                        ? array('handle' => '#' . $first->getHandle(), 'url' => $first->getSingleUrlArray()) : null;
                }
                $row['threadDeal'] = $threadDeals[$docThread];
            }
            if (isset($row['settled'])) {
                $Doc = $row['mvc'];
                $row['nonCash'] = !($Doc instanceof cash_Document || $Doc instanceof bank_Document || $Doc instanceof deals_DealMaster);
            }
            foreach ($totals as $field => $total) {
                if (!isset($row[$field])) continue;
                $row[$field] = round($row[$field], 2);
                if (empty($row['unpostedState'])) $totals[$field] += $row[$field];
            }
        }
        unset($row);

        usort($rows, static function ($a, $b) {
            return array($a['date'] ?? '9999-12-31', $a['id']) <=> array($b['date'] ?? '9999-12-31', $b['id']);
        });
        $rows = self::groupInvoiceNotes($rows);

        $incoming = $totals[$isSale ? 'settled' : 'delivered'];
        $outgoing = $totals[$isSale ? 'delivered' : 'settled'];
        $balance = round($incoming - $outgoing, 2);

        return array('rows' => $rows, 'totals' => $totals, 'balance' => $balance, 'currency' => $currency, 'partial' => $partial, 'isSale' => $isSale,
            'dealContainerId' => $rec->containerId ?? null);
    }


    /**
     * Изчислява участието на документите по конкретния договор.
     */
    private static function getDealRows($deal, $rec, $containers, $groupIds, $currency, $isSale, $unpostedStates, &$partial)
    {
        $dealId = $rec->id ?? null;
        $threadId = $rec->threadId ?? null;
        $dealValior = $rec->valior ?? null;
        $dealRate = ($rec->currencyRate ?? null) ?: 1;
        $dealCurrency = $rec->currencyId ?? acc_Periods::getBaseCurrencyCode($dealValior);
        $accounts = $isSale ? array('411', '412') : array('401', '402');
        $item = null;
        $entries = acc_Journal::getEntries(array($deal->className, $dealId), $item);
        $itemId = $item->id ?? null;
        $documents = self::getJournalDocuments($entries, $itemId, $accounts, $partial);

        foreach ($containers as $container) {
            $classId = $container->docClass ?? null;
            $id = $container->docId ?? null;
            if (!$classId || !$id) continue;

            $key = "{$classId}:{$id}";
            $Doc = $documents[$key]['mvc'] ?? cls::get($classId);
            $documents[$key] = ($documents[$key] ?? array()) + array('mvc' => $Doc, 'id' => $id);
            $state = $container->state ?? null;
            $documents[$key]['unpostedState'] = in_array($state, $unpostedStates, true) ? $state : null;
        }

        $rows = array();
        foreach ($documents as $key => $document) {
            $Doc = $document['mvc'];
            $docRec = $Doc->fetchRec($document['id']);
            $state = $docRec->state ?? ($document['unpostedState'] ?? null);
            $unposted = in_array($state, $unpostedStates, true);
            if ($state === 'rejected') continue;

            // Тези приключвания само прехвърлят обороти между договорите в групата.
            if ($Doc instanceof deals_ClosedDeals && isset($groupIds[$docRec->docId ?? null], $groupIds[$docRec->closeWith ?? null])
                && ($docRec->docClassId ?? null) == $deal->getClassId()) continue;

            $docEntries = $document['entries'] ?? array();
            if (!$unposted && !$docEntries && ($Doc instanceof deals_ClosedDeals
                || ($Doc instanceof acc_ValueCorrections && !empty($docRec->correspondingDealOriginId)
                    && $docRec->correspondingDealOriginId != ($rec->containerId ?? null)))) continue;

            if (!$docRec || !$Doc->haveRightFor('single', $docRec)
                || ($Doc->hasPlugin('doc_plg_HidePrices') && !doc_plg_HidePrices::canSeePriceFields($Doc, $docRec))) {
                $partial = $partial || !$unposted;
                continue;
            }

            $document['title'] = $Doc->singleTitle ?? $Doc->title ?? '';
            $document['state'] = $state;
            $document['unpostedState'] = $unposted ? $state : null;
            if (isset($document['state'])) {
                $document['stateTitle'] = $Doc->getVerbal($docRec, 'state');
            }
            $document['threadId'] = $docRec->threadId ?? $threadId;
            $document['otherThread'] = $document['threadId'] != $threadId;
            $document['date'] = $docRec->valior ?? ($docEntries[0]->valior ?? null);
            unset($document['entries']);

            $isInvoice = $Doc instanceof deals_InvoiceMaster;
            if ($isInvoice) {
                $type = $docRec->type ?? null;
                if ($type === 'dc_note') {
                    $document['title'] = ($docRec->dealValue ?? 0) <= 0 ? 'Кредитно известие' : 'Дебитно известие';
                } elseif ($Doc->getField('type', false)) {
                    $document['title'] = $Doc->getVerbal($docRec, 'type') ?? $document['title'];
                }
                $document['date'] = $docRec->date ?? null;
                if ($type === 'invoice') {
                    $document['invoiceContainerId'] = $docRec->containerId ?? null;
                } elseif (in_array($type, array('dc_note', 'credit_note', 'debit_note'), true)) {
                    $document['invoiceOriginId'] = $docRec->originId ?? null;
                }
                if (!empty($docRec->number)) {
                    $document['invoiceNumber'] = $Doc->getVerbal($docRec, 'number');
                }
            }

            try {
                $amounts = self::getDocumentAmounts($document, $docRec, $rec, $isInvoice ? $currency : $dealCurrency, $isSale);
                if (!$amounts && !$unposted && $docEntries && !$isInvoice) {
                    $isOwnDeal = $Doc === $deal && $document['id'] == $dealId;
                    $amounts = self::getJournalAmounts($docEntries, $itemId, $accounts, $Doc, $isOwnDeal, $isSale, $dealValior, $dealRate, $dealCurrency);
                    if (!$amounts) continue;
                }
                if (!$isInvoice && $currency !== $dealCurrency) {
                    foreach ($amounts as $field => $value) {
                        $amount = self::convertDocumentAmount($value, $dealCurrency, $currency, $document['date']);
                        if (isset($amount)) {
                            $amounts[$field] = $amount;
                        } else {
                            unset($amounts[$field]);
                            $partial = $partial || !$unposted;
                        }
                    }
                }
            } catch (core_exception_Expect $e) {
                reportException($e);
                $amounts = array();
                $partial = $partial || !$unposted;
            }
            if (!$amounts && $Doc instanceof deals_DealMaster) continue;
            $partial = $partial || (!$unposted && !$amounts);
            $document['amounts'] = $amounts;
            $rows[$key] = $document;
        }

        return $rows;
    }


    /**
     * Подрежда известията към видимите фактури, без да размества останалите редове.
     */
    private static function groupInvoiceNotes($rows)
    {
        $invoices = array();
        foreach ($rows as $index => $row) {
            if (!empty($row['invoiceContainerId'])) {
                $invoices[$row['invoiceContainerId']] = $index;
            }
        }

        foreach ($rows as $index => $row) {
            $invoiceIndex = $invoices[$row['invoiceOriginId'] ?? null] ?? null;
            if (isset($invoiceIndex) && $rows[$invoiceIndex]['mvc']->className === $row['mvc']->className) {
                $rows[$invoiceIndex]['invoiceNotes'][] = $row;
                unset($rows[$index]);
            }
        }

        foreach ($invoices as $index) {
            $invoice = $rows[$index];
            if (empty($invoice['invoiceNotes']) || !empty($invoice['unpostedState']) || !isset($invoice['invoiced'])) continue;

            $total = $invoice['invoiced'];
            foreach ($invoice['invoiceNotes'] as $note) {
                if (!empty($note['unpostedState'])) continue;
                if (!isset($note['invoiced'])) continue 2;

                $total += $note['invoiced'];
            }
            $rows[$index]['invoiceTotalWithNotes'] = round($total, 2);
        }

        return array_values($rows);
    }


    /**
     * Крайна сума на фактурата или известието за колоната „Фактурир.“.
     */
    private static function getInvoicedAmount($rec, $currency)
    {
        if (!isset($rec->dealValue)) return null;

        $total = ($rec->dealValue ?? 0) + ($rec->vatAmount ?? 0) - ($rec->discountAmount ?? 0);
        if (($rec->type ?? null) === 'credit_note') {
            $total = -$total;
        }

        $from = $rec->currencyId ?? null;
        $date = $rec->date ?? null;
        $rate = ($rec->displayRate ?? null) ?: ($rec->rate ?? null);

        return self::convertDocumentAmount(self::fromBaseAmount($total, $from, $rate, $date), $from, $currency, $date);
    }


    /**
     * Ограничаваме заявката до видовете документи, нужни за справката.
     */
    private static function getThreadDocuments($threads, $classes, $states)
    {
        $classIds = array();
        foreach ($classes as $class) {
            $classIds[] = core_Classes::getId($class);
        }

        $query = doc_Containers::getQuery();
        $query->in('threadId', $threads);
        $query->in('docClass', $classIds);
        $query->in('state', $states);
        $query->show('threadId,docClass,docId,state');

        return $query->fetchAll();
    }


    /**
     * Взема сумите от полетата, използвани в единичния изглед на документа.
     */
    private static function getDocumentAmounts($document, $rec, $dealRec, $currency, $isSale)
    {
        $Doc = $document['mvc'];
        $date = $document['date'];
        $sameThread = !$document['otherThread'];
        $dealContainerId = $dealRec->containerId ?? null;
        if ($Doc instanceof deals_InvoiceMaster) {
            $amount = self::getInvoicedAmount($rec, $currency);

            return isset($amount) ? array('invoiced' => $amount) : array();
        }

        if ($Doc instanceof deals_DealMaster) {
            if (!$sameThread || ($rec->doTransaction ?? 'yes') === 'no') return array();

            $actions = type_Set::toArray($rec->contoActions ?? null);
            $fields = array_intersect_key(array('ship' => 'delivered', 'pay' => 'settled'), $actions);
            if (!$fields) return array();

            $amount = self::fromBaseAmount($rec->amountDeal ?? null, $currency, $rec->currencyRate ?? null, $date);

            return isset($amount) ? array_fill_keys($fields, $amount) : array();
        }

        $sign = 1;
        if ($sameThread && ($Doc instanceof cash_Document || $Doc instanceof bank_Document)) {
            $isIncome = $Doc instanceof cash_Pko || $Doc instanceof bank_IncomeDocuments;
            $sign = $isIncome === $isSale ? 1 : -1;
            $field = 'settled';
            $amount = $rec->amountDeal ?? null;
            $currencyId = $rec->dealCurrencyId ?? null;
            $from = $currencyId ? currency_Currencies::getCodeById($currencyId) : $currency;
        } elseif ($Doc instanceof findeals_DebitDocuments || $Doc instanceof findeals_CreditDocuments) {
            $isCounterDeal = $dealContainerId && ($rec->dealId ?? null) == $dealContainerId;
            if (!$sameThread && !$isCounterDeal) return array();

            $isDebit = $Doc instanceof findeals_DebitDocuments;
            $sign = $isDebit === $isSale ? 1 : -1;
            $field = 'settled';
            $amount = $rec->amount ?? null;
            $from = $currency;
            if ($isCounterDeal) {
                $sign = -$sign;
                $amount = $rec->amountDeal ?? null;
                $currencyId = $rec->currencyId ?? null;
                $from = $currencyId ? currency_Currencies::getCodeById($currencyId) : null;
            }
        } elseif ($sameThread && ($Doc instanceof store_DocumentMaster || $Doc instanceof deals_ServiceMaster)) {
            $field = 'delivered';
            $sign = ($rec->isReverse ?? null) === 'yes' ? -1 : 1;
            $from = $rec->currencyId ?? null;
            $amount = self::fromBaseAmount($rec->amountDelivered ?? null, $from, $rec->currencyRate ?? null, $date);
        } elseif ($Doc instanceof acc_ValueCorrections) {
            $correctedDeal = $rec->correspondingDealOriginId ?? null;
            if (($correctedDeal ? $correctedDeal != $dealContainerId : !$sameThread)
                || !isset($rec->amount) || empty($rec->productsData) || ($rec->rate ?? 0) <= 0) return array();

            $payment = $Doc->getPaymentData($rec);
            $field = 'delivered';
            $sign = !empty($payment->isReverse) ? -1 : 1;
            $amount = $payment->amount ?? null;
            $from = $rec->currencyId ?? null;
        } else {
            return array();
        }

        $amount = self::convertDocumentAmount($amount, $from, $currency, $date);

        return isset($amount) ? array($field => $sign * $amount) : array();
    }


    /**
     * Възстановява показаната стойност по курса на документа.
     */
    private static function fromBaseAmount($amount, $currency, $rate, $date)
    {
        if (!isset($amount)) return null;

        return $rate > 0 ? $amount / $rate : self::convertDocumentAmount($amount, acc_Periods::getBaseCurrencyCode($date), $currency, $date);
    }


    /**
     * Обръща сумата във валутата на сделката, ако има надежден курс.
     */
    private static function convertDocumentAmount($amount, $from, $to, $date)
    {
        if (!isset($amount) || $amount == 0 || $from === $to) return $amount;
        if (!$from || !$to) return null;

        $converted = currency_CurrencyRates::convertAmount($amount, $date, $from, $to);

        return $converted != 0 ? $converted : null;
    }


    /**
     * Намира документите по перото без да изчислява сумите им от журнала.
     */
    private static function getJournalDocuments($entries, $itemId, $accounts, &$partial)
    {
        if (!$itemId || !$entries) return array();

        $accountIds = array();
        foreach ($accounts as $account) {
            $accountIds[] = acc_Accounts::getRecBySystemId($account)->id ?? null;
        }
        $documents = array();
        foreach ($entries as $entry) {
            $debit = ($entry->debitItem2 ?? null) == $itemId && in_array($entry->debitAccId ?? null, $accountIds);
            $credit = ($entry->creditItem2 ?? null) == $itemId && in_array($entry->creditAccId ?? null, $accountIds);
            if (!$debit && !$credit) continue;

            $classId = $entry->docType ?? null;
            $id = $entry->docId ?? null;
            if (!$classId || !$id) {
                $partial = true;
                continue;
            }
            $key = "{$classId}:{$id}";
            if (!isset($documents[$key])) {
                if (!cls::load($classId, true)) {
                    $partial = true;
                    continue;
                }
                $documents[$key] = array('mvc' => cls::get($classId), 'id' => $id);
            }
            $documents[$key]['entries'][] = $entry;
        }

        return $documents;
    }


    /**
     * Взема ефекта по сделката, когато документът не пази такава сума.
     */
    private static function getJournalAmounts($entries, $itemId, $accounts, $Doc, $isOwnDeal, $isSale, $valior, $rate, $currency)
    {
        $useCurrency = !in_array($currency, array('BGN', 'EUR'), true);
        $divisor = ($isSale ? 1 : -1) * ($useCurrency ? 1 : $rate);
        $getBalance = static function ($corresponding = null) use ($entries, $accounts, $itemId, $valior, $useCurrency, $divisor) {
            $result = acc_Balances::getBlAmounts($entries, $accounts, null, $corresponding, array(null, $itemId), array(), $valior, $useCurrency);

            return ($result->amount ?? 0) / $divisor;
        };
        $net = $getBalance();
        if ($isOwnDeal) {
            // Договорът може едновременно да доставя и плаща, дори при нулев нетен ефект.
            $settled = -$getBalance('501,503,481,482');
            $delivered = $net + $settled;
            $amounts = array();
            if ($settled != 0) $amounts['settled'] = $settled;
            if (round($delivered, 8) != 0) $amounts['delivered'] = $delivered;

            return $amounts;
        }
        if ($Doc instanceof store_DocumentMaster || $Doc instanceof deals_ServiceMaster || $Doc instanceof acc_ValueCorrections) {
            return array('delivered' => $net);
        }

        return $net != 0 ? array('settled' => -$net) : array();
    }


    /**
     * Подготвя съдържанието на клетките за един документ.
     */
    private static function getDocumentCells($document, $amountType, $dateType, $tabParams)
    {
        $Doc = $document['mvc'];
        $title = tr($document['title']);
        if (!empty($document['invoiceNumber'])) {
            $title .= ' № ' . $document['invoiceNumber'];
        }
        if ($document['otherThread']) {
            $threadDeal = $document['threadDeal'] ?? array();
            $threadLabel = $threadDeal['handle'] ?? tr('друга нишка');
            if (!empty($threadDeal['url'])) {
                $threadLabel = ht::createLink($threadLabel, $threadDeal['url'], false, array('target' => '_blank', 'rel' => 'noopener'));
            }
            $title .= ' ' . ht::createElement('small', array('class' => 'quiet'), '(' . $threadLabel . ')');
        }

        $linkAttr = array('class' => 'document-handler');
        if (isset($document['state'])) {
            $linkAttr['class'] .= ' state-' . $document['state'];
            $linkAttr['title'] = $document['stateTitle'] ?? '';
        }
        $id = $document['id'];
        if ($icon = $Doc->getIcon($id)) {
            $title = ht::createImg(array('path' => $icon, 'alt' => '', 'class' => 'deal-documents-icon')) . $title;
        }
        $url = $Doc->getSingleUrlArray($id);
        if ($document['otherThread'] || Mode::is('currentExternalTab', 'cms_Profiles')) {
            $linkAttr['target'] = '_blank';
            $linkAttr['rel'] = 'noopener';
        } elseif ($url) {
            $url += $tabParams;
        }

        $cells = array(
            'date' => $dateType->toVerbal($document['date'] ?? null) ?? '',
            'title' => $title,
            'handle' => ht::createLink('#' . $Doc->getHandle($id), $url, false, $linkAttr),
        );
        foreach (array('delivered', 'settled', 'invoiced') as $field) {
            $cells[$field] = isset($document[$field]) ? $amountType->toVerbal($document[$field]) : '';
        }
        if (!empty($document['nonCash'])) {
            $cells['settled'] = ht::createElement('small', array('class' => 'quiet'), tr('непарично')) . ' ' . $cells['settled'];
        }

        return $cells;
    }


    /**
     * Рендира документите и общите суми във валутата на сделката.
     */
    public static function render($summary)
    {
        $tpl = getTplFromFile('deals/tpl/DealDocuments.shtml');
        $tpl->push('deals/css/DocumentSummary.css', 'CSS');
        $amountType = core_Type::getByName('double(decimals=2)');
        $dateType = core_Type::getByName('date(format=d.m.Y)');
        $rowTpl = $tpl->getBlock('DOCUMENT_ROW');
        $currency = $summary['currency'];
        $dealContainerId = $summary['dealContainerId'] ?? null;
        $tabParams = $dealContainerId ? array("docTab{$dealContainerId}" => 'DealDocuments') : array();

        foreach ($summary['rows'] as $document) {
            $row = clone $rowTpl;
            $notes = $document['invoiceNotes'] ?? array();
            if ($notes) {
                $cells = array();
                foreach (array_merge(array($document), $notes) as $index => $lineDocument) {
                    $class = 'deal-documents-line';
                    if ($index > 0) $class .= ' deal-documents-note';
                    if (!empty($lineDocument['unpostedState'])) $class .= ' deal-documents-unposted';

                    foreach (self::getDocumentCells($lineDocument, $amountType, $dateType, $tabParams) as $field => $value) {
                        $cells[$field] = ($cells[$field] ?? '') . ht::createElement('span', array('class' => $class), $value ?: '&nbsp;');
                    }
                }
            } else {
                $cells = self::getDocumentCells($document, $amountType, $dateType, $tabParams);
            }

            if (isset($document['invoiceTotalWithNotes'])) {
                $hint = tr('Общо за фактурата с осчетоводените известия') . ': '
                    . $amountType->toVerbal($document['invoiceTotalWithNotes']) . ' ' . $currency;
                $cells['invoiced'] = ht::createHint($cells['invoiced'], $hint, 'noicon');
                $cells['invoiceHintClass'] = 'deal-documents-invoice-hint';
            }
            $cells['stateClass'] = !$notes && !empty($document['unpostedState']) ? 'deal-documents-unposted' : '';
            $row->placeArray($cells);
            $row->append2Master();
        }

        foreach ($summary['totals'] as $field => $total) {
            $tpl->replace($amountType->toVerbal($total), "{$field}Total");
        }
        $balance = $summary['balance'];
        $isSale = !empty($summary['isSale']);
        $delivered = tr('доставено');
        $settled = tr('платено');
        $balanceClass = $balance > 0 ? 'deal-documents-incoming' : ($balance < 0 ? 'deal-documents-outgoing' : '');
        $tpl->replace($amountType->toVerbal($balance), 'balance');
        $tpl->replace($balanceClass, 'balanceClass');
        $tpl->replace($isSale ? $settled : $delivered, 'incomingLabel');
        $tpl->replace($isSale ? $delivered : $settled, 'outgoingLabel');
        $tpl->replace($isSale ? 'deal-documents-sale' : 'deal-documents-purchase', 'directionClass');
        $tpl->replace($currency, 'currency');
        if (!empty($summary['rows'])) $tpl->removeBlock('NO_DOCUMENTS');
        if (!$summary['partial']) $tpl->removeBlock('PARTIAL');

        return $tpl;
    }
}
