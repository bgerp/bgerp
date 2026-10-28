<?php


/**
 * Библиотека за парсиране на трансакции от ISO 20022 XML
 *
 * @category  bgerp
 * @package   payment
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Правила при получаване на имейли » Обработка на ISO 20022 банкови извлечения
 */
class payment_ParserIso20022 extends core_BaseClass
{
    
    
    /**
     * Инрерфейси
     */
    public $interfaces = 'email_ServiceRulesIntf';
    
    
    /**
     * SPAM рейтинга, над който ще се игнорират
     */
    protected $maxSpamScore = 3;


    /**
     * Ограничения за автоматична обработка на прикачените извлечения.
     */
    const MAX_XML_BYTES = payment_ImportFiles::MAX_XML_BYTES;
    const MAX_ZIP_ENTRIES = payment_ImportFiles::MAX_ZIP_ENTRIES;


    /**
     * Добавяне на полета към наследниците
     */
    public static function addFields(&$mvc)
    {
        $mvc->FLD('archivePassword', 'password', 'caption=ZIP архиви->Парола,before=note,single=none');
    }


    /**
     * Запазва паролата при редакция без промяна на полето.
     */
    public static function on_AfterInputEditForm($Driver, $Embedder, &$form)
    {
        if ($form->isSubmitted()) {
            if (!isset($form->rec->archivePassword) && !empty($form->rec->id)) {
                $oldRec = $Embedder->fetch((int) $form->rec->id);
                $form->rec->archivePassword = $oldRec->archivePassword ?? null;
            }
            if (strpos($form->rec->archivePassword ?? '', "\0") !== false) {
                $form->setError('archivePassword', 'Паролата съдържа недопустим нулев байт');
            }
        }
    }

    
    /**
     * Парсира и връща обекти отговарящи на банковите трансакции в XML файла
     *
     * @param string $xml
     *
     * @return null|stdClass
     *                  o recs      array   Парсирани редове
     *                  o recordCurrencies array Валути по индексите на редовете
     *                  о warnings  array   Предупреждения
     *                  о errors    array   Грешки
     */
    public static function getRecs($xml, $serviceId = 'ISO20022 Import')
    {
        // Обект за върнатия резултат
        $res = new stdClass();
        $res->warnings = $res->errors = $res->recs = $res->recordCurrencies = array();
        $res->recordSources = array();
        $res->skippedEntries = 0;

        $error = null;
        $transactions = payment_XmlParser::load($xml, $error);
        if ($transactions === false) {
            $res->errors[] = $error;

            return $res;
        }

        $array = null;
        foreach ($transactions->getDocNamespaces() as $namespace) {
            if (preg_match('/^urn:iso:std:iso:20022:tech:xsd:camt\.(052|053)\.[0-9]+\.[0-9]+$/D', $namespace, $format)) {
                $document = $transactions->children($namespace);
                $array = $format[1] == '052' ? $document->BkToCstmrAcctRpt->Rpt : $document->BkToCstmrStmt->Stmt;
                break;
            }
        }
        
        if (!$array) {
            if (isset($document)) {
                $res->warnings[] = 'Липсва съдържание на CAMT извлечението';
                
                return $res;
            }
            
            return ;
        }

        // Циклим по частите за различните IBAN-ове
        foreach ($array as $stmt) {
            $res->skippedEntries += count($stmt->Ntry);
            $iban = (string) $stmt->Acct->Id->IBAN;
            $iban = strtoupper(preg_replace('/[^a-z0-9]/i', '', $iban));
            
            if(empty($iban)) {
                $res->warnings[] = "Празен IBAN";
                continue;
            }

            $bankAccRec = bank_Accounts::fetch(array("#iban = '[#1#]'", $iban));
            if (!$bankAccRec) {
                $res->warnings[] = "IBAN {$iban} липсва в списъка с банкови сметки";
                continue;
            }
            
            $ownBankAccRec = bank_OwnAccounts::fetch(array("#bankAccountId = '[#1#]'", $bankAccRec->id ?? null));
            if (!$ownBankAccRec) {
                $res->warnings[] = "Сметката с IBAN {$iban} не е собствена";
                continue;
            }
            
            $owrName = payment_XmlParser::getText($stmt, 'Acct/Ownr/Nm');
            $bank = payment_XmlParser::getText($stmt, 'Acct/Svcr/FinInstnId/Nm');
            $bic = payment_XmlParser::getText($stmt, 'Acct/Svcr/FinInstnId/BIC');
            
            $currency = strtoupper(trim((string) $stmt->Acct->Ccy));
            $pagination = $stmt->StmtPgntn ?? $document->BkToCstmrStmt->GrpHdr->MsgPgntn ?? $document->BkToCstmrAcctRpt->GrpHdr->MsgPgntn ?? null;
            if (!self::checkStatementTotals($stmt, $pagination, $iban, $res)) {
                continue;
            }

            $entryNumber = 0;
            foreach ($stmt->Ntry as $node) {
                $entryNumber++;
                $entryLabel = "IBAN {$iban}, операция №{$entryNumber}: ";
                if (isset($node->Sts)) {
                    $status = trim(payment_XmlParser::getText($node, isset($node->Sts->Cd) ? 'Sts/Cd' : 'Sts'));
                    $children = count($node->Sts->children($namespace));
                    if (count($node->Sts) != 1 || ($children && ($children != 1 || !isset($node->Sts->Cd)))
                        || (isset($node->Sts->Cd) && trim((string) $node->Sts) !== '')) {
                        $status = '';
                    }
                    if ($status !== 'BOOK') {
                        $res->warnings[] = $entryLabel . 'пропусната операция със статус ' . ($status !== '' ? $status : '(невалиден)') . '; импортират се само осчетоводени операции (BOOK)';
                        continue;
                    }
                }
                $direction = trim(payment_XmlParser::getText($node, 'CdtDbtInd'));
                if (!in_array($direction, array('CRDT', 'DBIT'), true)) {
                    $res->warnings[] = $entryLabel . "липсва или е невалиден видът на превода";
                    continue;
                }
                $amount = trim(payment_XmlParser::getText($node, 'Amt'));
                if (!payment_XmlParser::isValidAmount($amount)) {
                    $res->warnings[] = $entryLabel . "липсва или е невалидна сумата";
                    continue;
                }
                $rec = new stdClass();
                
                $rec->serviceId = $serviceId;
                $rec->ownAccountId = $ownBankAccRec->id;
                $rec->valior = (string) $node->ValDt->Dt;
                if (!$rec->valior) {
                    $rec->valior = substr((string) $node->ValDt->DtTm, 0, 10);
                }
                if (!$rec->valior) {
                    $rec->valior = (string) $node->BookgDt->Dt;
                }
                if (!$rec->valior) {
                    $rec->valior = substr((string) $node->BookgDt->DtTm, 0, 10);
                }
                if (!payment_XmlParser::isValidDate($rec->valior)) {
                    $res->warnings[] = $entryLabel . "липсва или е невалидна датата";
                    continue;
                }

                // BGN сметките преминават в EUR според датата на операцията.
                $expectedCurrency = bank_OwnAccounts::getDefaultCurrency($ownBankAccRec->id, $rec->valior, true);
                $amountCurrency = strtoupper(trim((string) ($node->Amt->attributes()['Ccy'] ?? '')));
                $recordCurrency = $amountCurrency !== '' ? $amountCurrency : ($currency !== '' ? $currency : $expectedCurrency);
                if (($currency !== '' && $currency != $expectedCurrency) || $recordCurrency != $expectedCurrency) {
                    $res->warnings[] = $entryLabel . "валутата към {$rec->valior} се различава от очакваната {$expectedCurrency}";
                    continue;
                }
                $rec->amount = (float) $amount;
                
                $reversal = strtolower(trim(payment_XmlParser::getText($node, 'RvslInd')));
                if (!in_array($reversal, array('', 'false', '0', 'true', '1'), true)) {
                    $res->warnings[] = $entryLabel . "невалиден признак за сторниране";
                    continue;
                }
                $isReversal = in_array($reversal, array('true', '1'), true);
                $entryTransactions = $transactionDirections = array();
                $complete = true;
                foreach ($node->NtryDtls ?? array() as $details) {
                    $batchDirection = trim(payment_XmlParser::getText($details, 'Btch/CdtDbtInd'));
                    $batchDirection = $batchDirection !== '' ? $batchDirection : $direction;
                    if (!in_array($batchDirection, array('CRDT', 'DBIT'), true)) {
                        $complete = false;
                    }
                    $count = trim(payment_XmlParser::getText($details, 'Btch/NbOfTxs'));
                    $detailTransactions = $details->TxDtls ?? array();
                    if ($count !== '' && (!ctype_digit($count) || (int) $count != count($detailTransactions))) {
                        $complete = false;
                    }
                    foreach ($detailTransactions as $transaction) {
                        $entryTransactions[] = $transaction;
                        $transactionDirections[] = $batchDirection;
                    }
                }
                if (!$complete) {
                    $res->warnings[] = $entryLabel . "непълни детайли или невалидна посока на групирания превод";
                    continue;
                }
                $grouped = count($entryTransactions) > 1;
                $entryTransactions = $entryTransactions ?: array(null);
                $rec->type = $direction === 'DBIT' ? 'outgoing' : 'incoming';
                $legacy = clone $rec;
                self::setCounterparty($legacy, $entryTransactions[0], $direction, false, $iban);
                $entryRecs = array();
                $total = 0;
                foreach ($entryTransactions as $transactionIndex => $transaction) {
                    $part = clone $rec;
                    $partDirection = $direction;
                    if ($grouped) {
                        $partDirection = trim(payment_XmlParser::getText($transaction, 'CdtDbtInd'));
                        $partDirection = $partDirection !== '' ? $partDirection : ($transactionDirections[$transactionIndex] ?? $direction);
                        $partAmount = $transaction->Amt ?? $transaction->AmtDtls->TxAmt->Amt ?? null;
                        $partValue = trim((string) $partAmount);
                        $partCurrency = isset($partAmount) ? strtoupper(trim((string) ($partAmount->attributes()['Ccy'] ?? ''))) : '';
                        if (!in_array($partDirection, array('CRDT', 'DBIT'), true)
                            || !payment_XmlParser::isValidAmount($partValue) || $partCurrency !== $recordCurrency
                            || abs((float) $partValue - round((float) $partValue, 2)) > 0.000001) {
                            $complete = false;
                            break;
                        }
                        $part->amount = (float) $partValue;
                    }
                    // При сторно CdtDbtInd е реалното движение, а страните са от първоначалния превод.
                    $part->type = $partDirection === 'DBIT' ? 'outgoing' : 'incoming';
                    self::setCounterparty($part, $transaction, $partDirection, $isReversal, $iban);
                    $part->reason = ($isReversal ? '[Сторно] ' : '') . self::getPaymentReason($node, $transaction);
                    $reference = self::getBankReference($node, $transaction, $grouped);
                    if ($reference !== '') {
                        $part->bankReference = 'CAMT:' . $recordCurrency . ':' . ($isReversal ? 'R:' : 'N:') . $reference;
                    }
                    $entryRecs[] = $part;
                    $total += (int) round($part->amount * 100) * ($partDirection === 'DBIT' ? -1 : 1);
                }
                $entryTotal = (int) round((float) $amount * 100) * ($direction === 'DBIT' ? -1 : 1);
                if (!$complete || ($grouped && ($total != $entryTotal || abs((float) $amount - round((float) $amount, 2)) > 0.000001))) {
                    $res->warnings[] = $entryLabel . "непълни или несъответстващи суми, валути или посоки в групирания превод";
                    continue;
                }
                $references = array();
                foreach ($entryRecs as $part) {
                    if (isset($part->bankReference)) {
                        $references[$part->bankReference] = ($references[$part->bankReference] ?? 0) + 1;
                    }
                }
                foreach ($entryRecs as $part) {
                    if (isset($part->bankReference) && $references[$part->bankReference] > 1) {
                        unset($part->bankReference);
                    }
                }
                // Старият импорт е записвал цялата група към първата страна; не добавяме втори комплект записи.
                if (($grouped || ($isReversal && ($legacy->contragentIban ?? '') !== ($entryRecs[0]->contragentIban ?? '')))
                    && self::hasLegacyEntry($legacy)) {
                    $res->warnings[] = $entryLabel . "има възможен запис от стария импорт; проверете банковия регистър преди повторна обработка";
                    continue;
                }
                foreach ($entryRecs as $part) {
                    $res->recs[] = $part;
                    $res->recordCurrencies[] = $recordCurrency;
                    $res->recordSources[] = array('iban' => $iban, 'entry' => $entryNumber);
                }
                $res->skippedEntries--;
            }
        }
        
        return $res;
    }
    
    
    /**
     * Контролите са върху оригиналните Ntry, преди филтриране и разделяне на групи.
     * При липса на контролни полета досегашният импорт остава непроменен.
     */
    private static function checkStatementTotals($stmt, $pagination, $iban, $res)
    {
        $prefix = "IBAN {$iban}: ";
        $summary = $stmt->TxsSummry ?? null;
        $balances = array();
        foreach ($stmt->Bal ?? array() as $balance) {
            $code = payment_XmlParser::getText($balance, 'Tp/CdOrPrtry/Cd');
            if (in_array($code, array('OPBD', 'CLBD'), true)) {
                $balances[$code][] = $balance;
            }
        }
        $checkBalances = isset($balances['OPBD'], $balances['CLBD']);
        if ($summary === null && !$checkBalances) {
            return true;
        }
        if ($pagination !== null && (trim(payment_XmlParser::getText($pagination, 'PgNb')) !== '1'
            || !in_array(strtolower(trim(payment_XmlParser::getText($pagination, 'LastPgInd'))), array('true', '1'), true))) {
            $res->warnings[] = $prefix . 'контролните суми и броят операции не са проверени: извлечението е на няколко страници';

            return true;
        }
        $errors = array();
        $counts = array('CRDT' => 0, 'DBIT' => 0);
        $sums = array('CRDT' => 0, 'DBIT' => 0);
        $booked = 0;
        $currency = strtoupper(trim(payment_XmlParser::getText($stmt, 'Acct/Ccy')));
        $canCheckBalance = true;
        foreach ($stmt->Ntry ?? array() as $entry) {
            $direction = trim(payment_XmlParser::getText($entry, 'CdtDbtInd'));
            $amount = self::controlAmount(payment_XmlParser::getText($entry, 'Amt'));
            $entryCurrency = strtoupper(trim((string) ($entry->Amt->attributes()['Ccy'] ?? '')));
            if ($currency === '') {
                $currency = $entryCurrency;
            }
            if ($amount === null || !isset($counts[$direction]) || ($entryCurrency !== '' && $currency !== $entryCurrency)) {
                $errors[] = 'не може да се провери извлечението поради невалидна сума, посока или различни валути';
                break;
            }
            $counts[$direction]++;
            $sums[$direction] += $amount;
            $status = isset($entry->Sts->Cd) ? payment_XmlParser::getText($entry, 'Sts/Cd') : payment_XmlParser::getText($entry, 'Sts');
            $status = trim($status);
            if (!isset($entry->Sts) || $status === 'BOOK') {
                $booked += $amount * ($direction === 'DBIT' ? -1 : 1);
            } elseif (!in_array($status, array('PDNG', 'INFO'), true)) {
                $canCheckBalance = false;
            }
        }
        if (!$errors && $summary !== null) {
            if (count($summary) != 1) {
                $errors[] = 'повече от един блок TxsSummry';
            }
            foreach (array('TtlNtries' => null, 'TtlCdtNtries' => 'CRDT', 'TtlDbtNtries' => 'DBIT') as $name => $direction) {
                if (!isset($summary->{$name})) {
                    continue;
                }
                $group = $summary->{$name};
                $expectedCount = $direction === null ? array_sum($counts) : $counts[$direction];
                $expectedSum = $direction === null ? array_sum($sums) : $sums[$direction];
                if (count($group) != 1) {
                    $errors[] = "повторен контролен блок {$name}";
                }
                if (isset($group->NbOfNtries)) {
                    $count = trim((string) $group->NbOfNtries);
                    if (count($group->NbOfNtries) != 1 || !ctype_digit($count) || strlen($count) > 15 || (int) $count !== $expectedCount) {
                        $errors[] = "{$name}/NbOfNtries: посоченият брой операции не съвпада с прочетените {$expectedCount}";
                    }
                }
                if (isset($group->Sum) && (count($group->Sum) != 1 || self::controlAmount((string) $group->Sum) !== $expectedSum)) {
                    $errors[] = "{$name}/Sum: контролната сума не съвпада със сбора на операциите";
                }
            }
            // Старите версии използват TtlNetNtryAmt, а новите - TtlNetNtry/Amt.
            $total = $summary->TtlNtries ?? null;
            $net = $total->TtlNetNtry ?? null;
            if (isset($total->TtlNetNtryAmt) && $net !== null) {
                $errors[] = 'две различни представяния на нетната контролна сума';
            }
            if (isset($total->TtlNetNtryAmt) || $net !== null) {
                $netAmount = self::controlAmount($net !== null ? payment_XmlParser::getText($net, 'Amt') : payment_XmlParser::getText($total, 'TtlNetNtryAmt'));
                $direction = trim(payment_XmlParser::getText($net ?? $total, 'CdtDbtInd'));
                if (($net !== null && (count($net) != 1 || count($net->Amt ?? array()) != 1 || count($net->CdtDbtInd ?? array()) != 1))
                    || (isset($total->TtlNetNtryAmt) && (count($total->TtlNetNtryAmt) != 1 || count($total->CdtDbtInd ?? array()) != 1))
                    || $netAmount === null || !isset($counts[$direction])
                    || $netAmount * ($direction === 'DBIT' ? -1 : 1) !== $sums['CRDT'] - $sums['DBIT']) {
                    $errors[] = 'нетната контролна сума не съвпада с кредитните минус дебитните операции';
                }
            }
        }
        if (!$errors && $checkBalances) {
            $values = $dates = array();
            foreach ($balances as $code => $items) {
                $balance = $items[0];
                $value = self::controlAmount(payment_XmlParser::getText($balance, 'Amt'));
                $direction = trim(payment_XmlParser::getText($balance, 'CdtDbtInd'));
                $balanceCurrency = strtoupper(trim((string) ($balance->Amt->attributes()['Ccy'] ?? '')));
                $date = payment_XmlParser::getText($balance, 'Dt/Dt');
                $date = $date !== '' ? $date : substr(payment_XmlParser::getText($balance, 'Dt/DtTm'), 0, 10);
                if (count($items) != 1 || $value === null || !isset($counts[$direction])) {
                    $errors[] = "невалидно или нееднозначно салдо {$code}";
                    continue;
                }
                if ($currency === '' || $balanceCurrency !== $currency || !payment_XmlParser::isValidDate($date)) {
                    $canCheckBalance = false;
                }
                $values[$code] = $value * ($direction === 'DBIT' ? -1 : 1);
                $dates[$code] = $date;
            }
            if (!$errors) {
                if (!$canCheckBalance || $dates['OPBD'] > $dates['CLBD']) {
                    $res->warnings[] = $prefix . 'салдата не са проверени: липсват съпоставими валути, дати или статуси';
                } elseif ($values['OPBD'] + $booked !== $values['CLBD']) {
                    $errors[] = 'начално салдо + осчетоводени постъпления - осчетоводени плащания не е равно на крайното салдо';
                }
            }
        }
        foreach (array_unique($errors) as $error) {
            $res->errors[] = $prefix . $error . '; извлечението за тази сметка не се импортира';
        }

        return !$errors;
    }


    /**
     * Точни стотинки за контролите, без float закръгляне и с по-голям лимит от отделен превод.
     */
    private static function controlAmount($value)
    {
        $value = trim($value ?? '');
        $value = preg_replace('/^(\+?)\./', '${1}0.', $value);
        if (!preg_match('/^\\+?([0-9]+)(?:\\.([0-9]*))?$/D', $value, $parts)) {
            return null;
        }
        $whole = ltrim($parts[1], '0');
        $fraction = rtrim($parts[2] ?? '', '0');
        if (strlen($whole) > 13 || strlen($fraction) > 2) {
            return null;
        }

        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }


    /**
     * Само референцията на обслужващата банка е ключ; клиентските номера могат да се повтарят.
     * Общата референция на Ntry не идентифицира отделните детайли на групиран превод.
     */
    private static function getBankReference($entry, $transaction, $grouped)
    {
        $nodes = array($transaction->Refs->AcctSvcrRef ?? null);
        if (!$grouped) {
            $nodes[] = $entry->AcctSvcrRef ?? null;
        }
        foreach ($nodes as $node) {
            $value = trim((string) $node);
            if ($node === null || count($node) != 1 || strlen($value) > 100 || preg_match('/[\x00-\x1f\x7f]/', $value)) {
                continue;
            }
            $placeholder = strtoupper(preg_replace('/[^a-z0-9]/i', '', $value));
            if ($placeholder === '' || preg_match('/^0+$/D', $placeholder)
                || in_array($placeholder, array('NOTPROVIDED', 'NONREF', 'NOTAVAILABLE', 'UNKNOWN', 'NA', 'NONE'), true)) {
                continue;
            }

            return $value;
        }

        return '';
    }


    /**
     * Страните при сторно запазват ролите от първоначалния превод.
     */
    private static function setCounterparty($rec, $transaction, $direction, $reversal, $ownIban)
    {
        $creditor = ($direction === 'DBIT') !== $reversal;
        $party = $creditor ? 'Cdtr' : 'Dbtr';
        $rec->contragentIban = payment_XmlParser::getText($transaction, "RltdPties/{$party}Acct/Id/IBAN");
        if ($rec->contragentIban === $ownIban) {
            $party = $creditor ? 'Dbtr' : 'Cdtr';
            $rec->contragentIban = payment_XmlParser::getText($transaction, "RltdPties/{$party}Acct/Id/IBAN");
        }
        $rec->contragentName = payment_XmlParser::getText($transaction, "RltdPties/{$party}/Nm");
        if ($rec->contragentName === '') {
            $rec->contragentName = payment_XmlParser::getText($transaction, "RltdPties/{$party}/Pty/Nm");
        }
    }


    /**
     * Без банков идентификатор съвпадение със стар общ запис изисква човешка проверка.
     */
    private static function hasLegacyEntry($rec)
    {
        return (bool) bank_Register::fetch(array(
            "#ownAccountId = '[#1#]' AND #valior = '[#2#]' AND #type = '[#3#]' AND #amount = '[#4#]' AND #contragentIban = '[#5#]' AND #serviceId LIKE '%ISO20022%'",
            $rec->ownAccountId ?? null, $rec->valior ?? null, $rec->type ?? null, $rec->amount ?? null, $rec->contragentIban ?? ''
        ));
    }


    /**
     * Допълва досегашното основание с платежния текст и референциите към документи.
     */
    private static function getPaymentReason($node, $transaction)
    {
        $reason = payment_XmlParser::getText($transaction, 'AddtlTxInf');
        if ($reason === '') {
            $reason = payment_XmlParser::getText($node, 'AddtlNtryInf');
        }
        $parts = $reason !== '' ? array($reason) : array();
        $remittance = $transaction->RmtInf ?? null;
        if ($remittance !== null) {
            foreach ($remittance->Ustrd ?? array() as $text) {
                $parts[] = trim((string) $text);
            }
            foreach ($remittance->Strd ?? array() as $structured) {
                $parts[] = trim(payment_XmlParser::getText($structured, 'CdtrRefInf/Ref'));
                foreach ($structured->RfrdDocInf ?? array() as $document) {
                    $parts[] = trim(payment_XmlParser::getText($document, 'Nb'));
                }
                foreach ($structured->AddtlRmtInf ?? array() as $text) {
                    $parts[] = trim((string) $text);
                }
            }
        }

        return implode(' ', array_unique(array_filter($parts, function ($part) { return $part !== ''; })));
    }


    /**
     * Чете файл или съдържанието на ZIP, без да разпакетира пътища на диска.
     * Паролата на конкретния файл има предимство пред тази на правилото.
     */
    protected static function getAttachmentContents($fh, $password = null)
    {
        return payment_ImportFiles::readContents($fh, $password);
    }


    /**
     * Проверява дали в $mime се съдържа спам писмо и ако е
     * така - съхранява го за определено време в този модел
     *
     * @param email_Mime $mime
     * @param stdClass   $serviceRec
     *
     * @return string|false false, ако писмото не е обработено
     *
     * @see email_ServiceRulesIntf
     */
    public function process($mime, $serviceRec)
    {
        $sScore = email_Spam::getSpamScore($mime->parts[1]->headersArr ?? array(), true, $mime);
        
        if ($sScore >= $this->maxSpamScore) {
            
            log_System::add(get_called_class(), "Не е подходящ за ISO20022 - висок спам резултат: {$sScore}", null, 'debug', 3);
            
            return false;
        }

        $mime->saveFiles();
        
        $filesKeyList = $mime->getFiles();
        $filesArr = keylist::toArray($filesKeyList);
        
        if (empty($filesArr)) {
            
            log_System::add(get_called_class(), "Не е подходящ за ISO20022 - няма файлове", null, 'debug', 3);
            
            return false;
        }

        $res = false;
        $failed = false;
        
        foreach ($filesArr as $fId) {
            $fh = fileman::fetchField($fId, 'fileHnd');
            // Правилото остава само за CAMT; ръчният импорт поддържа и Unicredit XML.
            $parsed = payment_ImportFiles::getRecs($fh, $serviceRec->archivePassword ?? null, 'EMAIL', false);
            foreach (array_merge($parsed->errors ?? array(), $parsed->warnings ?? array()) as $message) {
                fileman::logWarning('ISO20022: ' . $message, $fId);
            }
            if (($parsed->status ?? null) == 'unsupported') {
                fileman::logNotice('ISO20022: Пропуснат файл без разпознато банково извлечение', $fId);
                continue;
            }
            if (!empty($parsed->errors) || !empty($parsed->warnings)) {
                $failed = true;
            }
            if (!empty($parsed->recs)) {
                foreach ($parsed->batches ?? array() as $batch) {
                    $importWarnings = array();
                    $status = bank_Register::importRecs($batch, null, $importWarnings);
                    if ($importWarnings) {
                        $failed = true;
                        foreach ($importWarnings as $message) {
                            fileman::logWarning('ISO20022: ' . $message, $fId);
                        }
                    }
                    fileman::logNotice('ISO20022: ' . $status, $fId);
                }
                $matches = bank_Register::findMatches();
                fileman::logNotice("ISO20022: Обработени {$matches} записа", $fId);
            } elseif (!empty($parsed->recognized)) {
                fileman::logNotice('ISO20022: Няма записи', $fId);
            }
            if (!empty($parsed->recognized)) {
                $res = 'ISO20022';
            }
            if (($parsed->status ?? null) == 'partial') {
                fileman::logWarning('ISO20022: Частичен импорт - валидните операции са обработени; проверете останалите', $fId);
            }
        }

        if ($failed && $res) {
            log_System::add(get_called_class(), 'Частична обработка на извлеченията - писмото остава за проверка', null, 'notice');
        }

        return $failed ? false : $res;
    }
}
