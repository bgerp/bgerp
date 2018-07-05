<?php


/**
 * Библиотека за парсиране на трансакции от Unicredit XML
 *
 * @category  bgerp
 * @package   payment
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Парсиране на Unicredit XML Файл
 */
class payment_ParserUC
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
    public static function getRecs($xml, $serviceId = 'Unicredit Import')
    {
        // Обект за върнатия резултат
        $res = new stdClass();
        $res->warnings = $res->errors = $res->recs = array();

        // Вземаме SimpleXMLElement обект, отговарящ на файла
        $transactions = new SimpleXMLElement($xml);
   
        // Циклим по частите за различните IBAN-ове
        foreach ($transactions->ArrayOfAPAccounts->APAccount as $stmt) {
            $iban = (string) $stmt->BankAccount->IBAN;
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

            $owrName = (string) $stmt->BankClient->NAME;
            // $bank    = (string) $stmt->Acct->Svcr->FinInstnId->Nm;
            $bic = (string) $stmt->BankAccount->BIC - Code;
            
            // Проверка дали валутата на блока, отговаря на валутата на нашата сметка
            $currency = strtoupper($stmt->BankAccount->CCY->SWIFTCode);
            if ($currency && $currency != currency_Currencies::getCodeById($bankAccRec->currencyId)) {
                $res->warnings[] = "Валутата за IBAN {$iban} се различава от тази в сметката";
                continue;
            }
 
            foreach ($stmt->BankAccount->BankAccountMovements->ArrayOfBankAccountMovements->BankAccountMovement as $node) {
                $rec = new stdClass();
                
                $rec->serviceId = $serviceId;
                $rec->ownAccountId = $ownBankAccRec->id;
                list($rec->valior, ) = explode('T', (string) $node->ValDate);
                $rec->amount = (float) $node->MovementAmount;

                if ($node->MovementType != 2) {
                    $rec->type = 'outgoing';
                    $rec->contragentIban = (string) $node->PayeeIBAN;
                    $rec->contragentName = (string) $node->PayeeName;
                } else {
                    $rec->type = 'incoming';
                    $rec->contragentIban = (string) $node->PayeeIBAN;
                    $rec->contragentName = (string) $node->PayeeName;
                }

                $rec->reason = (string) $node->Reason . ' ' . $node->Reason2 . ' ' . $node->Narrative . ' ' . $node->NarrativeI02;
 
                // Добавяме реда в резултата
                $res->recs[] = $rec;
            }
        }

        return $res;
    }
}
