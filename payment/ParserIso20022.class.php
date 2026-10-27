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
    const MAX_XML_BYTES = 20971520;
    const MAX_ZIP_ENTRIES = 100;


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
                
                $rec->reason = self::getXmlText($node, 'NtryDtls/TxDtls/AddtlTxInf');
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
     * Чете файл или съдържанието на ZIP, без да разпакетира пътища на диска.
     * Паролата на конкретния файл има предимство пред тази на правилото.
     */
    protected static function getAttachmentContents($fh, $password = null)
    {
        $path = fileman_Files::fetchByFh($fh, 'path');
        $size = $path && is_readable($path) ? filesize($path) : false;
        if ($size === false || $size > self::MAX_XML_BYTES) {
            throw new fileman_Exception('Извлечението липсва или надвишава 20 MB');
        }

        $signature = file_get_contents($path, false, null, 0, 4);
        $name = fileman_Files::fetchByFh($fh, 'name');
        if (strtolower(fileman::getExt($name)) != 'zip' && !in_array($signature, array("PK\x03\x04", "PK\x05\x06"), true)) {
            $content = file_get_contents($path, false, null, 0, self::MAX_XML_BYTES + 1);
            if ($content === false || strlen($content) > self::MAX_XML_BYTES) {
                throw new fileman_Exception('Не може да се прочете извлечението');
            }

            return array($content);
        }

        if (!class_exists('ZipArchive')) {
            throw new fileman_Exception('За ZIP извлечения е необходимо PHP разширението zip');
        }
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CHECKCONS) !== true) {
            throw new fileman_Exception('Не може да се отвори ZIP извлечението');
        }

        try {
            $filePassword = fileman_webdrv_Generic::getPassword($fh);
            if ($filePassword !== null) {
                $password = $filePassword;
            }
            if (isset($password) && $password !== '') {
                if (strpos($password, "\0") !== false || !$zip->setPassword($password)) {
                    throw new fileman_Exception('Невалидна парола за ZIP извлечението');
                }
            }
            if ($zip->numFiles > self::MAX_ZIP_ENTRIES) {
                throw new fileman_Exception('ZIP извлечението съдържа повече от 100 записа');
            }

            $contents = array();
            $totalSize = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if ($stat === false || !isset($stat['size'])) {
                    throw new fileman_Exception('Не може да се прочете съдържанието на ZIP извлечението');
                }
                $entrySize = $stat['size'];
                $totalSize += $entrySize;
                if ($entrySize < 0 || $totalSize > self::MAX_XML_BYTES) {
                    throw new fileman_Exception('Разархивираното извлечение надвишава 20 MB');
                }
                if (!$entrySize) {
                    continue;
                }
                $content = @$zip->getFromIndex($i, self::MAX_XML_BYTES + 1);
                if ($content === false || strlen($content) != $entrySize) {
                    throw new fileman_Exception('Не може да се прочете ZIP извлечението. Проверете паролата на файла или на правилото');
                }
                $contents[] = $content;
            }

            return $contents;
        } finally {
            $zip->close();
        }
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
            try {
                $contents = static::getAttachmentContents($fh, $serviceRec->archivePassword ?? null);
            } catch (fileman_Exception $e) {
                fileman::logWarning('ISO20022: ' . $e->getMessage(), $fId);
                $failed = true;
                continue;
            }

            foreach ($contents as $xml) {
                // Форматът се разпознава по съдържанието, включително при .camt.
                if (strpos($xml, 'urn:iso:std:iso:20022:tech:xsd:camt.') === false) {
                    continue;
                }
                
                $parsed = $this->getRecs($xml, 'EMAIL ISO20022');
                
                if (!$parsed) {
                    fileman::logWarning('Не може да се извлекат данни за ISO20022', $fId);
                    $failed = true;
                    
                    continue;
                }
                
                if (!empty($parsed->warnings)) {
                    fileman::logWarning('ISO20022 предупреждения: ' . implode(' ', $parsed->warnings ?? array()), $fId);
                    $failed = true;
                }
                
                if (!empty($parsed->errors)) {
                    fileman::logWarning('ISO20022 грешки: ' . implode(' ', $parsed->errors ?? array()), $fId);
                    $failed = true;
                }
                
                if (empty($parsed->recs)) {
                    fileman::logNotice('ISO20022: Няма записи', $fId);
                } else {
                    $status = bank_Register::importRecs($parsed->recs ?? array());
                    fileman::logNotice('ISO20022: ' . $status, $fId);
                    
                    $matches = bank_Register::findMatches();
                    fileman::logNotice("ISO20022: Обработени {$matches} записа", $fId);
                }
                
                $res = 'ISO20022';
            }
        }
        
        return $failed ? false : $res;
    }
}
