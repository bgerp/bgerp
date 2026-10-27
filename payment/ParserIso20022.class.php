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

        $xml = ltrim($xml, "\xEF\xBB\xBF \t\r\n");
        if (stripos($xml, '<!DOCTYPE') !== false) {
            $res->errors[] = 'XML извлечението не трябва да съдържа DTD';

            return $res;
        }
        
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
            
            $owrName = self::getXmlText($stmt, 'Acct/Ownr/Nm');
            $bank = self::getXmlText($stmt, 'Acct/Svcr/FinInstnId/Nm');
            $bic = self::getXmlText($stmt, 'Acct/Svcr/FinInstnId/BIC');
            
            $currency = strtoupper(trim((string) $stmt->Acct->Ccy));

            $entryNumber = 0;
            foreach ($stmt->Ntry as $node) {
                $entryNumber++;
                $direction = trim(self::getXmlText($node, 'CdtDbtInd'));
                if (!in_array($direction, array('CRDT', 'DBIT'), true)) {
                    $res->warnings[] = "Операция №{$entryNumber}: липсва или е невалиден видът на превода";
                    continue;
                }
                $amount = trim(self::getXmlText($node, 'Amt'));
                if (!preg_match('/^\+?(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)$/D', $amount)
                    || !is_finite((float) $amount) || (float) $amount > 2000000000) {
                    $res->warnings[] = "Операция №{$entryNumber}: липсва или е невалидна сумата";
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
                $rec->amount = (float) $amount;
                
                if ($direction == 'DBIT') {
                    $rec->type = 'outgoing';
                    $rec->contragentIban = self::getXmlText($node, 'NtryDtls/TxDtls/RltdPties/CdtrAcct/Id/IBAN');
                    $rec->contragentName = self::getXmlText($node, 'NtryDtls/TxDtls/RltdPties/Cdtr/Nm');
                    if ($rec->contragentIban == $iban) {
                        $rec->contragentIban = self::getXmlText($node, 'NtryDtls/TxDtls/RltdPties/DbtrAcct/Id/IBAN');
                        $rec->contragentName = self::getXmlText($node, 'NtryDtls/TxDtls/RltdPties/Dbtr/Nm');
                    }
                } else {
                    $rec->type = 'incoming';
                    $rec->contragentIban = self::getXmlText($node, 'NtryDtls/TxDtls/RltdPties/DbtrAcct/Id/IBAN');
                    $rec->contragentName = self::getXmlText($node, 'NtryDtls/TxDtls/RltdPties/Dbtr/Nm');
                    if ($rec->contragentIban == $iban) {
                        $rec->contragentIban = self::getXmlText($node, 'NtryDtls/TxDtls/RltdPties/CdtrAcct/Id/IBAN');
                        $rec->contragentName = self::getXmlText($node, 'NtryDtls/TxDtls/RltdPties/Cdtr/Nm');
                    }
                }
                
                $rec->reason = self::getPaymentReason($node);
                
                // Добавяме реда в резултата
                $res->recs[] = $rec;
                $res->recordCurrencies[] = $recordCurrency;
            }
        }
        
        return $res;
    }
    
    
    /**
     * Текстът на вложен XML възел; празен низ, ако някой възел по пътя липсва
     *
     * @param SimpleXMLElement $node
     * @param string           $path  Имена на възлите, разделени с /
     *
     * @return string
     */
    private static function getXmlText($node, $path)
    {
        foreach (explode('/', $path) as $name) {
            if (!isset($node->{$name})) {

                return '';
            }
            $node = $node->{$name};
        }

        return (string) $node;
    }


    /**
     * Допълва досегашното основание с платежния текст и референциите към документи.
     */
    private static function getPaymentReason($node)
    {
        $reason = self::getXmlText($node, 'NtryDtls/TxDtls/AddtlTxInf');
        if ($reason === '') {
            $reason = self::getXmlText($node, 'AddtlNtryInf');
        }
        $parts = $reason !== '' ? array($reason) : array();
        $remittance = $node->NtryDtls->TxDtls->RmtInf ?? null;
        if ($remittance !== null) {
            foreach ($remittance->Ustrd ?? array() as $text) {
                $parts[] = trim((string) $text);
            }
            foreach ($remittance->Strd ?? array() as $structured) {
                $parts[] = trim(self::getXmlText($structured, 'CdtrRefInf/Ref'));
                foreach ($structured->RfrdDocInf ?? array() as $document) {
                    $parts[] = trim(self::getXmlText($document, 'Nb'));
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
                    $status = bank_Register::importRecs($batch);
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
