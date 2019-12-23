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
 * @title     Парсиране на ISO 20022 XML Файл
 */
class payment_ParserIso20022
{
    /**
     * Парсира и връща обекти отговарящи на банковите трансакции в XML файла
     *
     * @param string $xml
     *
     * @return stdClass
     *                  o recs      array   Парсирани редове
     *                  о warnings  array   Предупреждения
     *                  о errors    array   Грешки
     */
    public static function getRecs($xml, $serviceId = 'ISO20022 Import')
    {
        // Обект за върнатия резултат
        $res = new stdClass();
        $res->warnings = $res->errors = $res->recs = array();
        
        // Вземаме SimpleXMLElement обект, отговарящ на файла
        $transactions = new SimpleXMLElement($xml);
        
        // Циклим по частите за различните IBAN-ове
        foreach ($transactions->BkToCstmrStmt->Stmt as $stmt) {
            $iban = (string) $stmt->Acct->Id->IBAN;
            $iban = strtoupper(preg_replace('/[^a-z0-9]/i', '', $iban));
            
            $bankAccRec = bank_Accounts::fetch("#iban = '{$iban}'");
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
            
            // Проверка дали валутата на блока, отговаря на валутата на нашата сметка
            $currency = strtoupper($stmt->Acct->Ccy);
            if ($currency && $currency != currency_Currencies::getCodeById($bankAccRec->currencyId)) {
                $res->warnings[] = "Валутата за IBAN {$iban} се различава от тази в сметката";
                continue;
            }
            
            foreach ($stmt->Ntry as $node) {
                $rec = new stdClass();
                
                $rec->serviceId = $serviceId;
                $rec->ownAccountId = $ownBankAccRec->id;
                $rec->valior = (string) $node->ValDt->Dt;
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
            }
        }
        
        return $res;
    }
}
