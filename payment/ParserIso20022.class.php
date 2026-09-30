<?php


/**
 * Библиотека за парсиране на трансакции от ISO 20022 XML
 *
 * @category  bgerp
 * @package   payment
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Правила при получаване на имейли » Обработка на ISO 22000 банкови извлечения
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
     * Добавяне на полета към наследниците
     */
    public static function addFields(&$mvc)
    {
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
        
        // Вземаме SimpleXMLElement обект, отговарящ на файла
        $previous = libxml_use_internal_errors(true);
        try {
            $transactions = simplexml_load_string(trim($xml), 'SimpleXMLElement', LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($transactions === false) {

            $res->warnings[] = "Грешка при парсиране на XML";

            return $res;
        }

        if(strpos(implode('|', $transactions->getNamespaces()), 'camt.052') !== false) {
            $array = $transactions->BkToCstmrAcctRpt->Rpt;
        } else {
            $array = $transactions->BkToCstmrStmt->Stmt;
        }
        
        if (!$array) {
            if (strpos(implode('|', $transactions->getNamespaces()), 'camt.053') !== false) {
                $res->warnings[] = "Друг формата - camt.053";
                
                return $res;
            }
            
            return ;
        }

        // Циклим по частите за различните IBAN-ове
        foreach ($array as $stmt) {
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
            
            $ownBankAccRec = bank_OwnAccounts::fetch("#bankAccountId = {$bankAccRec->id}");
            if (!$ownBankAccRec) {
                $res->warnings[] = "Сметката с IBAN {$iban} не е собствена";
                continue;
            }
            
            $owrName = (string) $stmt->Acct->Ownr->Nm;
            $bank = (string) $stmt->Acct->Svcr->FinInstnId->Nm;
            $bic = (string) $stmt->Acct->Svcr->FinInstnId->BIC;
            
            $currency = strtoupper(trim((string) $stmt->Acct->Ccy));

            foreach ($stmt->Ntry as $node) {
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
                if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $rec->valior, $dateParts)
                    || !checkdate((int) $dateParts[2], (int) $dateParts[3], (int) $dateParts[1])) {
                    $res->warnings[] = "Липсва или е невалидна датата на операция за IBAN {$iban}";
                    continue;
                }

                // BGN сметките преминават в EUR според датата на операцията.
                $expectedCurrency = bank_OwnAccounts::getDefaultCurrency($ownBankAccRec->id, $rec->valior, true);
                $amountCurrency = strtoupper(trim((string) $node->Amt['Ccy']));
                $recordCurrency = $amountCurrency ?: ($currency ?: $expectedCurrency);
                if (($currency && $currency != $expectedCurrency) || $recordCurrency != $expectedCurrency) {
                    $res->warnings[] = "Валутата за IBAN {$iban} към {$rec->valior} се различава от очакваната {$expectedCurrency}";
                    continue;
                }
                $rec->amount = (float) $node->Amt;
                
                if ($node->CdtDbtInd == 'DBIT') {
                    $rec->type = 'outgoing';
                    $rec->contragentIban = (string) $node->NtryDtls->TxDtls->RltdPties->CdtrAcct->Id->IBAN;
                    $rec->contragentName = (string) $node->NtryDtls->TxDtls->RltdPties->Cdtr->Nm;
                    if ($rec->contragentIban == $iban) {
                        $rec->contragentIban = (string) $node->NtryDtls->TxDtls->RltdPties->DbtrAcct->Id->IBAN;
                        $rec->contragentName = (string) $node->NtryDtls->TxDtls->RltdPties->Dbtr->Nm;
                    }
                } else {
                    $rec->type = 'incoming';
                    $rec->contragentIban = (string) $node->NtryDtls->TxDtls->RltdPties->DbtrAcct->Id->IBAN;
                    $rec->contragentName = (string) $node->NtryDtls->TxDtls->RltdPties->Dbtr->Nm;
                    if ($rec->contragentIban == $iban) {
                        $rec->contragentIban = (string) $node->NtryDtls->TxDtls->RltdPties->CdtrAcct->Id->IBAN;
                        $rec->contragentName = (string) $node->NtryDtls->TxDtls->RltdPties->Cdtr->Nm;
                    }
                }
                
                $rec->reason = (string) $node->NtryDtls->TxDtls->AddtlTxInf;
                if (!$rec->reason) {
                    $rec->reason = (string) $node->AddtlNtryInf;
                }
                
                // Добавяме реда в резултата
                $res->recs[] = $rec;
                $res->recordCurrencies[] = $recordCurrency;
            }
        }
        
        return $res;
    }
    
    
    /**
     * Проверява дали в $mime се съдържа спам писмо и ако е
     * така - съхранява го за определено време в този модел
     *
     * @param email_Mime $mime
     * @param stdClass   $serviceRec
     *
     * @return string|null
     *
     * @see email_ServiceRulesIntf
     */
    public function process($mime, $serviceRec)
    {
        $sScore = email_Spam::getSpamScore($mime->parts[1]->headersArr, true, $mime);
        
        if ($sScore >= $this->maxSpamScore) {
            
            log_System::add(get_called_class(), "Не е подходящ за ISO20022 - висок спам резултат: {$sScore}", null, 'debug', 3);
            
            return ;
        }

        $mime->saveFiles();
        
        $filesKeyList = $mime->getFiles();
        $filesArr = keylist::toArray($filesKeyList);
        
        if (empty($filesArr)) {
            
            log_System::add(get_called_class(), "Не е подходящ за ISO20022 - няма файлове", null, 'debug', 3);
            
            return ;
        }

        $res = null;
        
        foreach ($filesArr as $fId) {
            $fName = fileman::fetchField($fId, 'name');
            $fExt = fileman::getExt($fName);

            if ($fExt == 'xml') {
                $fh = fileman::fetchField($fId, 'fileHnd');
                $xml = fileman::extractStr($fh);

                $isoPos = strpos($xml, 'iso:20022');

                if (($isoPos === false) || ($isoPos > 100)) {

                    fileman::logWarning('ISO20022 стринга е много назад', $fId);

                    continue;
                }
                
                $res = $this->getRecs($xml, 'EMAIL ISO20022');
                
                if (!$res) {
                    fileman::logWarning('Не може да се извлекат данни за ISO20022', $fId);
                    
                    continue;
                }
                
                if (countR($res->warnings)) {
                    fileman::logWarning('ISO20022 предупреждения: ' . implode(' ', $res->warnings), $fId);
                }
                
                if (countR($res->errors)) {
                    fileman::logWarning('ISO20022 грешки: ' . implode(' ', $res->errors), $fId);
                }
                
                if (!countR($res->recs)) {
                    fileman::logNotice('ISO20022: Няма записи', $fId);
                } else {
                    $status = bank_Register::importRecs($res->recs);
                    fileman::logNotice('ISO20022: ' . $status, $fId);
                    
                    $res = bank_Register::findMatches();
                    fileman::logNotice("ISO20022: Обработени {$res} записа", $fId);
                }
                
                $res = 'ISO20022';
            }
        }
        
        return $res;
    }
}
