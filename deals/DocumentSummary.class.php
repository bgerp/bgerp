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
     * Извлича документите по перото, включително тези от други нишки.
     */
    public static function prepare($deal, $rec)
    {
        $dealId = $rec->id ?? null;
        $dealValior = $rec->valior ?? null;
        $dealRate = ($rec->currencyRate ?? null) ?: 1;
        $currency = $rec->currencyId ?? acc_Periods::getBaseCurrencyCode($dealValior);
        $isSale = $deal instanceof sales_Sales;
        $sign = $isSale ? 1 : -1;
        $item = null;
        $entries = acc_Journal::getEntries(array($deal->className, $dealId), $item);
        $partial = false;
        $documents = self::getPostedDocuments($entries, $item->id ?? null, $deal->getClassId(), $dealId, $isSale, $dealValior, $dealRate, $currency, $partial);

        $threadId = $rec->threadId ?? null;
        $threads = empty($rec->closedDocuments) ? array($threadId) : deals_Helper::getCombinedThreads($threadId);
        $unpostedStates = array('draft', 'pending');
        $invoiceClasses = array('sales_Invoices', 'purchase_Invoices');
        $unpostedClasses = array(
            $deal->className, 'store_ShipmentOrders', 'store_Receipts', 'sales_Services', 'purchase_Services',
            'cash_Pko', 'cash_Rko', 'bank_IncomeDocuments', 'bank_SpendingDocuments',
            'findeals_DebitDocuments', 'findeals_CreditDocuments', 'acc_ValueCorrections', 'sales_ClosedDeals', 'purchase_ClosedDeals',
        );
        $containers = self::getThreadDocuments($threads, $invoiceClasses, array_merge(array('active'), $unpostedStates));
        $containers += self::getThreadDocuments(array($threadId), $unpostedClasses, $unpostedStates);
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
        $totals = array('delivered' => 0, 'settled' => 0, 'invoiced' => 0);
        foreach ($documents as $document) {
            $Doc = $document['mvc'];
            $unposted = !empty($document['unpostedState']);
            $docRec = $Doc->fetchRec($document['id']);
            if (!$docRec || !$Doc->haveRightFor('single', $docRec)
                || ($Doc->hasPlugin('doc_plg_HidePrices') && !doc_plg_HidePrices::canSeePriceFields($Doc, $docRec))) {
                $partial = $partial || !$unposted;
                continue;
            }

            $document['title'] = $Doc->singleTitle ?? $Doc->title ?? '';
            $document['state'] = $docRec->state ?? null;
            if (isset($document['state'])) {
                $document['stateTitle'] = $Doc->getVerbal($docRec, 'state');
            }
            $document['otherThread'] = ($docRec->threadId ?? $threadId) != $threadId;
            if ($unposted) {
                $document = self::prepareUnpostedAmounts($document, $docRec, $rec, $isSale, $dealRate);
                if ($Doc instanceof deals_DealMaster && !isset($document['delivered']) && !isset($document['settled'])) continue;
            }

            if ($Doc instanceof deals_InvoiceMaster) {
                try {
                    $document['invoiced'] = self::getInvoicedAmount($docRec, $dealValior, $dealRate);
                } catch (core_exception_Expect $e) {
                    reportException($e);
                    $partial = $partial || !$unposted;
                }

                $type = $docRec->type ?? null;
                if ($type === 'dc_note') {
                    $document['title'] = ($docRec->dealValue ?? 0) <= 0 ? 'Кредитно известие' : 'Дебитно известие';
                } elseif ($Doc->getField('type', false)) {
                    $document['title'] = $Doc->getVerbal($docRec, 'type') ?? $document['title'];
                }
                $document['date'] = $docRec->date ?? null;
                if ($type === 'invoice' && !empty($docRec->number)) {
                    $document['invoiceNumber'] = $Doc->getVerbal($docRec, 'number');
                }
            }

            if (($Doc instanceof cash_Document || $Doc instanceof bank_Document) && isset($document['settled'])) {
                try {
                    $document = self::usePaymentAmount($document, $docRec, $currency);
                } catch (core_exception_Expect $e) {
                    reportException($e);
                    if ($unposted) unset($document['settled']);
                }
            }

            foreach ($totals as $field => $total) {
                if (!isset($document[$field])) continue;
                $document[$field] = round($document[$field], 2);
                if (!$unposted) $totals[$field] += $document[$field];
            }
            $rows[] = $document;
        }

        usort($rows, static function ($a, $b) {
            return array($a['date'] ?? '9999-12-31', $a['id']) <=> array($b['date'] ?? '9999-12-31', $b['id']);
        });

        $balance = $isSale ? sales_transaction_Sale::getBlAmount($entries, $rec) : purchase_transaction_Purchase::getBlAmount($entries, $rec);
        $balance = round($balance / $dealRate, 2);
        $difference = $partial ? 0 : round($balance - $sign * ($totals['delivered'] - $totals['settled']), 2);

        return array('rows' => $rows, 'totals' => $totals, 'balance' => $balance, 'currency' => $currency, 'partial' => $partial, 'difference' => $difference);
    }


    /**
     * Крайна сума на фактурата или известието за колоната „Фактурир.“.
     */
    private static function getInvoicedAmount($rec, $dealValior, $dealRate)
    {
        $total = ($rec->dealValue ?? 0) + ($rec->vatAmount ?? 0) - ($rec->discountAmount ?? 0);
        if (($rec->type ?? null) === 'credit_note') {
            $total = -$total;
        }
        if (($rec->currencyId ?? null) === 'EUR') {
            return deals_Helper::getSmartBaseCurrency($total, $rec->date ?? null, $dealValior) / $dealRate;
        }

        $displayRate = ($rec->displayRate ?? null) ?: ($rec->rate ?? null);
        expect($displayRate > 0, 'Липсва валутен курс на фактурата', $rec->id ?? null);

        return $total / $displayRate;
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
        $query->show('docClass,docId,state');

        return $query->fetchAll();
    }


    /**
     * Черновите и заявките нямат запис в журнала, затова четем сумите от документа.
     */
    private static function prepareUnpostedAmounts($document, $rec, $dealRec, $isSale, $dealRate)
    {
        $Doc = $document['mvc'];
        $document['date'] = $rec->valior ?? null;
        unset($document['delivered'], $document['settled']);
        if ($Doc instanceof cash_Document || $Doc instanceof bank_Document) {
            $isIncome = $Doc instanceof cash_Pko || $Doc instanceof bank_IncomeDocuments;
            if (isset($rec->amountDeal)) $document['settled'] = ($isIncome === $isSale ? 1 : -1) * abs($rec->amountDeal);
        } elseif ($Doc instanceof findeals_DebitDocuments || $Doc instanceof findeals_CreditDocuments) {
            $isDebit = $Doc instanceof findeals_DebitDocuments;
            if (isset($rec->amount)) $document['settled'] = ($isDebit === $isSale ? 1 : -1) * $rec->amount;
            $document['nonCash'] = true;
        } elseif ($Doc instanceof store_DocumentMaster || $Doc instanceof deals_ServiceMaster) {
            if (isset($rec->amountDelivered)) {
                $valior = $dealRec->valior ?? null;
                $sign = ($rec->isReverse ?? null) == 'yes' ? -1 : 1;
                if (($rec->currencyId ?? null) == ($dealRec->currencyId ?? null) && !empty($rec->currencyRate)) {
                    $document['delivered'] = $sign * $rec->amountDelivered / $rec->currencyRate;
                } else {
                    $document['delivered'] = $sign * deals_Helper::getSmartBaseCurrency($rec->amountDelivered, $document['date'] ?? $valior, $valior) / $dealRate;
                }
            }
        } elseif ($Doc instanceof acc_ValueCorrections && isset($rec->amount)) {
            $vatRate = 0;
            if (in_array($dealRec->chargeVat ?? null, array('yes', 'separate'), true) && !empty($rec->productsData)) {
                $vatRate = $Doc->getAverageVatRate($rec->productsData, $rec);
            }
            $valior = $dealRec->valior ?? null;
            $sign = ($rec->action ?? null) == 'decrease' ? -1 : 1;
            $amount = deals_Helper::getSmartBaseCurrency($rec->amount, $document['date'] ?? $valior, $valior) / $dealRate;
            $document['delivered'] = $sign * $amount * (1 + $vatRate);
        } elseif ($Doc instanceof deals_DealMaster) {
            $actions = type_Set::toArray($rec->contoActions ?? null);
            $amount = ($rec->amountDeal ?? 0) / $dealRate;
            if (!empty($actions['ship'])) $document['delivered'] = $amount;
            if (!empty($actions['pay'])) $document['settled'] = $amount;
        }

        return $document;
    }


    /**
     * Групира контировките по документ и взема само страната на текущата сделка.
     */
    private static function getPostedDocuments($entries, $itemId, $dealClassId, $dealId, $isSale, $valior, $rate, $currency, &$partial)
    {
        if (!$itemId) return array();

        $groups = $documents = array();
        foreach ($entries as $entry) {
            $classId = $entry->docType ?? null;
            $id = $entry->docId ?? null;
            if (!$classId || !$id) {
                $partial = true;
                continue;
            }
            $groups["{$classId}:{$id}"][] = $entry;
        }

        $accounts = $isSale ? '411,412' : '401,402';
        $useCurrency = !in_array($currency, array('BGN', 'EUR'), true);
        $divisor = ($isSale ? 1 : -1) * ($useCurrency ? 1 : $rate);
        $getBalance = static function ($group, $corresponding = null) use ($accounts, $itemId, $valior, $useCurrency) {
            return acc_Balances::getBlAmounts($group, $accounts, null, $corresponding, array(null, $itemId), array(), $valior, $useCurrency);
        };

        foreach ($groups as $key => $group) {
            $result = $getBalance($group);
            if (empty($result->recs)) continue;

            $entry = reset($group);
            $classId = $entry->docType ?? null;
            $id = $entry->docId ?? null;
            $net = ($result->amount ?? 0) / $divisor;
            if (!cls::load($classId, true)) {
                $partial = $partial || $net != 0;
                continue;
            }

            $Doc = cls::get($classId);
            $document = array('mvc' => $Doc, 'id' => $id, 'date' => $entry->valior ?? null);
            if ($classId == $dealClassId && $id == $dealId) {
                // Договорът може едновременно да доставя и плаща, дори при нулев нетен ефект.
                $payment = $getBalance($group, '501,503,481,482');
                $settled = -($payment->amount ?? 0) / $divisor;
                $delivered = $net + $settled;
                if ($settled != 0) $document['settled'] = $settled;
                if (round($delivered, 8) != 0) $document['delivered'] = $delivered;
            } elseif ($Doc instanceof store_DocumentMaster || $Doc instanceof deals_ServiceMaster || $Doc instanceof acc_ValueCorrections) {
                $document['delivered'] = $net;
            } elseif ($net != 0) {
                $document['settled'] = -$net;
                $document['nonCash'] = !($Doc instanceof cash_Document || $Doc instanceof bank_Document);
            }

            if (isset($document['settled']) || isset($document['delivered'])) {
                $documents[$key] = $document;
            }
        }

        return $documents;
    }


    /**
     * Запазва сумата „Платени по сделката“ и взема знака от движението по перото.
     */
    private static function usePaymentAmount($document, $rec, $currency)
    {
        $amount = $rec->amountDeal ?? null;
        if (!isset($amount)) return $document;

        $currencyId = $rec->dealCurrencyId ?? null;
        $paymentCurrency = $currencyId ? currency_Currencies::getCodeById($currencyId) : $currency;
        if ($paymentCurrency && $paymentCurrency != $currency) {
            $converted = currency_CurrencyRates::convertAmount($amount, $document['date'] ?? null, $paymentCurrency, $currency);
            if ($converted == 0 && $amount != 0) {
                if (!empty($document['unpostedState'])) unset($document['settled']);

                return $document;
            }
            $amount = $converted;
        }

        $settled = ($document['settled'] < 0 ? -1 : 1) * abs($amount);
        if (empty($document['unpostedState']) && round($settled, 2) != round($document['settled'], 2)) {
            $document['postedAmount'] = $document['settled'];
        }
        $document['settled'] = $settled;

        return $document;
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

        foreach ($summary['rows'] as $document) {
            $row = clone $rowTpl;
            $Doc = $document['mvc'];
            $row->replace($dateType->toVerbal($document['date'] ?? null) ?? '', 'date');
            $title = tr($document['title']);
            if (!empty($document['invoiceNumber'])) {
                $title .= ' № ' . $document['invoiceNumber'];
            }
            $row->replace($title, 'title');

            $linkAttr = array('ef_icon' => false, 'class' => 'document-handler');
            if (isset($document['state'])) {
                $linkAttr['class'] .= ' state-' . $document['state'];
                $linkAttr['title'] = $document['stateTitle'] ?? '';
            }
            $row->replace($Doc->getLink($document['id'], 0, $linkAttr), 'handle');
            $row->replace(empty($document['unpostedState']) ? '' : 'deal-documents-unposted', 'stateClass');
            if (!$document['otherThread']) $row->removeBlock('OTHER_THREAD');
            if (empty($document['nonCash'])) $row->removeBlock('NON_CASH');
            foreach ($summary['totals'] as $field => $total) {
                $amount = isset($document[$field]) ? $amountType->toVerbal($document[$field]) : '';
                if ($field == 'settled' && isset($document['postedAmount'])) {
                    $hint = tr('Счетоводен ефект') . ': ' . $amountType->toVerbal($document['postedAmount']) . ' ' . $summary['currency'];
                    $amount = ht::createElement('span', array('title' => $hint), $amount);
                }
                $row->replace($amount, $field);
            }
            $row->append2Master();
        }

        foreach ($summary['totals'] as $field => $total) {
            $tpl->replace($amountType->toVerbal($total), "{$field}Total");
        }
        // Отрицателното счетоводно салдо е наше задължение и при покупка, и при продажба.
        $balance = $summary['balance'];
        $tpl->replace(ht::styleNumber($amountType->toVerbal($balance), -$balance, 'green'), 'balance');
        $tpl->replace($summary['currency'], 'currency');
        if (!empty($summary['rows'])) $tpl->removeBlock('NO_DOCUMENTS');
        if (!$summary['partial']) $tpl->removeBlock('PARTIAL');
        if ($summary['difference'] != 0) {
            $tpl->replace($amountType->toVerbal($summary['difference']), 'difference');
        } else {
            $tpl->removeBlock('DIFFERENCE');
        }

        return $tpl;
    }
}
