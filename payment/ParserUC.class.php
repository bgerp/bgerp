<?php


/**
 * Библиотека за парсиране на трансакции от Unicredit XML
 *
 * @category  bgerp
 * @package   payment
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
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
        
        $error = null;
        $transactions = payment_XmlParser::load($xml, $error);
        if ($transactions === false) {
            $res->errors[] = $error;

            return $res;
        }
        if (!isset($transactions->ArrayOfAPAccounts)) {
            $res->errors[] = 'Липсва съдържание на Unicredit извлечението';

            return $res;
        }

        // Циклим по частите за различните IBAN-ове
        foreach ($transactions->ArrayOfAPAccounts->APAccount as $stmt) {
            $iban = payment_XmlParser::getText($stmt, 'BankAccount/IBAN');
            $iban = strtoupper(preg_replace('/[^a-z0-9]/i', '', $iban));
            
            if ($iban === '') {
                $res->warnings[] = 'Празен IBAN';
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

            $owrName = payment_XmlParser::getText($stmt, 'BankClient/NAME');
            $bic = payment_XmlParser::getText($stmt, 'BankAccount/BIC-Code');

            $currency = strtoupper(trim(payment_XmlParser::getText($stmt, 'BankAccount/CCY/SWIFTCode')));
            $movements = $stmt->BankAccount->BankAccountMovements->ArrayOfBankAccountMovements ?? null;
            $entryNumber = 0;
            foreach ($movements->BankAccountMovement ?? array() as $node) {
                $entryNumber++;
                $direction = trim(payment_XmlParser::getText($node, 'MovementType'));
                $amount = trim(payment_XmlParser::getText($node, 'MovementAmount'));
                $valior = explode('T', trim(payment_XmlParser::getText($node, 'ValDate')))[0];
                if (!in_array($direction, array('1', '2'), true)) {
                    $res->warnings[] = "Операция №{$entryNumber}: липсва или е невалиден видът на превода";
                    continue;
                }
                if (!payment_XmlParser::isValidAmount($amount)) {
                    $res->warnings[] = "Операция №{$entryNumber}: липсва или е невалидна сумата";
                    continue;
                }
                if (!payment_XmlParser::isValidDate($valior)) {
                    $res->warnings[] = "Операция №{$entryNumber}: липсва или е невалидна датата";
                    continue;
                }
                $expectedCurrency = bank_OwnAccounts::getDefaultCurrency($ownBankAccRec->id ?? null, $valior, true);
                if ($currency !== '' && $currency != $expectedCurrency) {
                    $res->warnings[] = "Валутата за IBAN {$iban} към {$valior} се различава от очакваната {$expectedCurrency}";
                    continue;
                }
                $rec = new stdClass();
                $rec->serviceId = $serviceId;
                $rec->ownAccountId = $ownBankAccRec->id ?? null;
                $rec->valior = $valior;
                $rec->amount = (float) $amount;
                $rec->type = $direction === '2' ? 'incoming' : 'outgoing';
                $rec->contragentIban = payment_XmlParser::getText($node, 'PayeeIBAN');
                $rec->contragentName = payment_XmlParser::getText($node, 'PayeeName');

                $rec->reason = payment_XmlParser::getText($node, 'Reason') . ' ' . payment_XmlParser::getText($node, 'Reason2') . ' ' . payment_XmlParser::getText($node, 'Narrative') . ' ' . payment_XmlParser::getText($node, 'NarrativeI02');
                
                // Добавяме реда в резултата
                $res->recs[] = $rec;
            }
        }
        
        return $res;
    }
}
