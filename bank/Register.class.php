<?php


/**
 * Регистър за импортиране на банкови плащания
 *
 *
 * @category  bgerp
 * @package   bank
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bank_Register extends core_Manager
{
    public $title = 'Регистър за банковите трансакции';

    public $singleTitle = 'Банкова трансакция';

    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'bank_Wrapper, plg_State, plg_Created, plg_Modified, plg_GroupByField,plg_RowTools2,import2_Plugin';


    /**
     * Интерфейс на драйверите за импортиране
     */
    public $importInterface = 'bank_ImportTransactionsIntf';


    /**
     * По кое поле да се направи групиране
     */
    public $groupByField = 'valiorAndIban';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valiorAndIban, amount, contragentName=Контрагент, info=Осчетоводяване';


    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'bank, ceo';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'bank, ceo';


    /**
     * Кой може да създава?
     */
    public $canAdd = 'bank, ceo';


    /**
     * Кой може да редактира?
     */
    public $canEdit = 'bank, ceo';


    /**
     * Кой може да го контира?
     */
    public $canConto = 'bank, ceo';


    /**
     * Добавяне на дефолтни полета
     *
     * @param core_Mvc $mvc
     *
     * @return void
     */
    public function description()
    {
        $this->FLD('serviceId', 'varchar(32)', 'caption=Услуга');
        $this->FLD('transactionId', 'varchar(32)', 'caption=Услуга');

        $this->FLD('type', 'enum(incoming=Входящ,outgoing=Изходящ)', 'caption=Вид');
        $this->FLD('amount', 'double(decimals=2,max=2000000000,min=0)', 'caption=Сума');
        $this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор');
        $this->FLD('ownAccountId', 'key(mvc=bank_OwnAccounts,select=title,allowEmpty)', 'caption=Наша сметка');
        $this->FLD('reason', 'varchar', 'caption=Основание');

        $this->FLD('contragentName', 'varchar(255)', 'caption=Контрагент->Име');
        $this->FLD('contragentIban', 'varchar(255)', 'caption=Контрагент->Сметка');

        $this->FLD('matches', 'blob(compress,serialize)', 'caption=Съответствия,input=none,oldFieldName=accounting');

        $this->FLD('state', 'enum(waiting=Чакащ, active=Активиран, rejected=Оттеглен)', 'caption=Състояние');

        $this->FNC('valiorAndIban', 'varchar', 'captin=Дата и IBAN');

        $this->setDbUnique('transactionId');
    }


    /**
     * Поддръжка на функционално поле
     */
    public function on_CalcValiorAndIban($mvc, $rec)
    {
        $rec->valiorAndIban = $rec->valior . '|' . $rec->ownAccountId;
    }


    /**
     * Вербализира групата
     */
    public function renderGroupName($data, $groupId, $groupVerbal)
    {
        list($valior, $ownBankAccId) = explode('|', $groupId);

        $valior = dt::mysql2verbal($valior, 'd/m/Y');

        $ownBankAcc = bank_OwnAccounts::getTitleById($ownBankAccId);

        if ($currencyCode = self::getCurrencyCodeByAccount($ownBankAccId)) {
            $ownBankAcc .= ' ' . $currencyCode;
        }

        $res = "<h3>{$valior}, {$ownBankAcc}</h3>";

        return $res;
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (!empty($rec->contragentIban)) {
            $row->contragentName = ($row->contragentName ?? '') . (!empty($row->contragentName) ? '<br>' : '') . $mvc->getVerbal($rec, 'contragentIban');
        }
        if (!empty($rec->reason)) {
            $row->contragentName = ($row->contragentName ?? '') . (!empty($row->contragentName) ? '<br>' : '') . '<small>' . $mvc->getVerbal($rec, 'reason') . '<small>';
        }

        // Валутата на трансакцията е тази на нашата сметка
        $transCurrencyCode = self::getCurrencyCodeByAccount($rec->ownAccountId);

        if (!empty($transCurrencyCode)) {
            $row->amount = ($row->amount ?? '') . ' <small>' . $transCurrencyCode . '</small>';
        }

        $row->amount = ($row->amount ?? '') . '<br><small>' . mb_strtolower($mvc->getVerbal($rec, 'type')) . '</small>';

        if ($rec->type == 'outgoing') {
            $color = '#800';
        } else {
            $color = '#008';
        }

        if (!empty($rec->matches['folderId'])) {
            $folderId = $rec->matches['folderId'];
            $params = array();
            $params['folderId'] = $folderId;

            $folder = doc_Folders::getVerbalLink($params);

            if ($folder === false) {
                $folder = tr('Липсващ обект');
            } else {
                $folder = $folder->getContent();
            }

            $row->info = $folder;
        }

        if (!empty($rec->matches['rows']) && is_array($rec->matches['rows'])) {
            $t = '';
            foreach ($rec->matches['rows'] as $r) {
                $t .= "\n<tr>";
                $parts = array('head', 'prof', 'inv', 'bdoc');

                foreach ($parts as $part) {
                    // Проформите
                    $t .= '<td>';
                    $first = true;
                    if (isset($r->{$part}) && is_array($r->{$part})) {
                        foreach ($r->{$part} as $p) {
                            $link = ht::createLink(
                                $p->documentMvc->abbr . $p->number,
                                array($p->documentMvc, 'Single', $p->documentId, 'ret_url' => true),
                                null,
                                array('title' => $p->documentMvc->singleTitle . ' · ' . self::getAmountHint($p, $transCurrencyCode))
                            ) ;
                            $t .= $first ? '' : '<br>';
                            $t .= $link->getContent() . self::getMatchBadge($p);
                            $first = false;
                        }
                    }
                    if ($part == 'bdoc') {
                        $mvc = ($rec->type == 'incoming') ? 'bank_IncomeDocuments' : 'bank_PaymentOrders';

                        $Document = doc_Containers::getDocument($r->containerId);
                        $FirstDoc = doc_Threads::getFirstDocument($Document->fetchField('threadId'));
                        $dealCurrencyId = $FirstDoc->fetchField('currencyId');

                        $ownAccountCurrencyId = bank_OwnAccounts::getOwnAccountInfo($rec->ownAccountId)->currencyId;
                        $ownAccountCurrencyCode = currency_Currencies::getCodeById($ownAccountCurrencyId);
                        $inDealCurrencyAmount = currency_CurrencyRates::convertAmount($rec->amount, null, $ownAccountCurrencyCode, $dealCurrencyId);
                        $inDealCurrencyAmount = round($inDealCurrencyAmount, 2);
                        $rec->amount = round($rec->amount, 2);

                        $url = array($mvc, 'add', 'originId' => $FirstDoc->fetchField('containerId'),  'amountDeal' =>  $inDealCurrencyAmount, 'fromContainerId' => $r->containerId, 'ret_url' => true);
                        if($rec->amount != $inDealCurrencyAmount){
                            $url['amount'] = $rec->amount;
                        }

                        $link = ht::createLink('+', $url) ;
                        $t .= $first ? '' : '<br>';
                        $t .= $link->getContent();
                        $first = false;
                    }
                    $t .= '</td>';
                }
                $t .= '</tr>';
            }

            $row->info = ($row->info ?? '') . "<table style='font-size:0.8em' class='listTable'>" . $t . '</table>';
        }

        // Съответствия, намерени по подготвените (чакащи) банкови документи
        if (!empty($rec->matches['all']) && is_array($rec->matches['all'])) {
            foreach ($rec->matches['all'] as $folderId => $docs) {
                $params = array('folderId' => $folderId);
                $folderLink = doc_Folders::getVerbalLink($params);

                if ($folderLink === false) {
                    $title = tr('Липсващ обект');
                } else {
                    $title = $folderLink->getContent();
                }

                $row->info = ($row->info ?? '') . $title . "<ul>\n";

                foreach ($docs as $d) {
                    $Doc = cls::get($d->documentMvc);
                    $dRec = $Doc->fetch($d->documentId);

                    if (!$dRec) {
                        continue;
                    }

                    $state = 'state-' . $dRec->state;
                    $link = ht::createLink(
                        $Doc->abbr . $d->documentId,
                        array($Doc, 'Single', $d->documentId, 'ret_url' => true),
                        null,
                        array('title' => $Doc->singleTitle . ' · ' . self::getAmountHint($d, $transCurrencyCode))
                    );

                    $row->info .= "<li><div style='display:inline-block;padding:3px;border-radius:3px;' class='{$state}'>" . $link->getContent() . '</div>' . self::getMatchBadge($d) . '</li>';
                }

                $row->info .= "</ul>\n";
            }
        }

        $row->ROW_ATTR['style'] = ($row->ROW_ATTR['style'] ?? '') . "color:{$color};";
    }


    /**
     * Кода на валутата на нашата сметка - мемоизирано, защото се вика на всеки ред от листа
     *
     * @param int $ownAccountId
     *
     * @return string|null
     */
    public static function getCurrencyCodeByAccount($ownAccountId)
    {
        static $cache = array();

        if (empty($ownAccountId)) {

            return null;
        }

        if (!array_key_exists($ownAccountId, $cache)) {
            $currencyId = self::getCurrencyByAccount($ownAccountId);
            $cache[$ownAccountId] = $currencyId ? currency_Currencies::getCodeById($currencyId) : null;
        }

        return $cache[$ownAccountId];
    }


    /**
     * Подсказка за сумата на документа - в неговата валута и, при разлика, в тази на трансакцията
     *
     * @param stdClass    $d                 - документ от `matches`
     * @param string|null $transCurrencyCode - валутата на трансакцията
     *
     * @return string
     */
    public static function getAmountHint($d, $transCurrencyCode)
    {
        $code = $d->currencyCode ?? null;
        $res = number_format((float) ($d->amount ?? 0), 2, '.', '');

        if (!empty($code)) {
            $res .= ' ' . $code;

            if (!empty($transCurrencyCode) && $code != $transCurrencyCode && isset($d->amountInTransCurrency)) {
                $res .= ' (≈ ' . number_format((float) $d->amountInTransCurrency, 2, '.', '') . ' ' . $transCurrencyCode . ')';
            }
        }

        return $res;
    }


    /**
     * Бадж с оценката на съвпадението - сам разпознава кой алгоритъм я е записал
     *
     * Двете скали са различни и нарочно се показват различно: разнасянето по сделки и фактури
     * трупа сурови точки (сборът може да мине 2.0, прагът е 0.5), а разнасянето по чакащи
     * документи мери три величини в диапазон 0-1.
     *
     * @param stdClass $d - документ от `matches`
     *
     * @return string
     */
    public static function getMatchBadge($d)
    {
        $captions = array('number' => 'номер', 'amount' => 'сума', 'date' => 'дата', 'folder' => 'папка');

        // Разнасяне по сделки и фактури - сурови точки с разбивка
        if (isset($d->p) && !empty($d->pParts) && is_array($d->pParts)) {
            $hint = array();
            foreach ($d->pParts as $key => $value) {
                $hint[] = tr($captions[$key] ?? $key) . ' ' . ($value > 0 ? '+' : '') . number_format((float) $value, 2, '.', '');
            }

            $caption = number_format((float) $d->p, 2, '.', '') . ' ' . tr('т.');
            $level = ($d->p >= 1) ? 'good' : (($d->p >= 0.7) ? 'mid' : 'weak');

            return self::renderBadge($caption, implode(' · ', $hint), $level);
        }

        // Разнасяне по чакащи банкови документи - трите метрики и кое правило е сработило
        if (isset($d->p) && isset($d->rule)) {
            $hint = tr('име') . ' ' . number_format((float) ($d->sameName ?? 0), 2, '.', '') .
                ' · ' . tr('сума') . ' ' . number_format((float) ($d->sameSum ?? 0), 2, '.', '') .
                ' · ' . tr('основание') . ' ' . number_format((float) ($d->hasReason ?? 0), 2, '.', '') .
                ' (' . tr(($d->rule == 'reason') ? 'по основание и сума' : 'по име и сума') . ')';

            $caption = round($d->p * 100) . '%';
            $level = ($d->p >= 0.9) ? 'good' : (($d->p >= 0.7) ? 'mid' : 'weak');

            return self::renderBadge($caption, $hint, $level);
        }

        return '';
    }


    /**
     * Рендира бадж с оценка
     *
     * @param string $caption - какво пише в баджа
     * @param string $hint    - разбивката, показва се като подсказка
     * @param string $level   - good|mid|weak
     *
     * @return string
     */
    private static function renderBadge($caption, $hint, $level)
    {
        $colors = array('good' => '#2a7d4f', 'mid' => '#b8860b', 'weak' => '#888');
        $color = $colors[$level] ?? $colors['weak'];
        $hint = str_replace(array('"', '<', '>'), '', $hint);

        return " <span style='background:{$color};color:#fff;padding:0 4px;border-radius:3px;font-size:0.85em;white-space:nowrap' title=\"{$hint}\">{$caption}</span>";
    }


    /**
     * Импортира подготвени редове в модела
     */
    public static function importRecs($recs, $serviceId = null)
    {
        $usedIds = array();
        $ins = $skip = 0;

        foreach ($recs as $rec) {
            if ($serviceId) {
                $rec->serviceId = $serviceId;
            }
            $rec->state = 'waiting';
            $ind = 0;
            do {
                $rec->transactionId = md5("{$rec->valior}|{$rec->type}|{$rec->amount}|{$rec->contragentIban}|{$rec->ownAccountId}|{$ind}");
                $ind++;
            } while (!empty($usedIds[$rec->transactionId]));

            $usedIds[$rec->transactionId] = true;

            if (self::fetch("#transactionId = '{$rec->transactionId}'")) {
                $skip++;
            } else {
                self::save($rec, null, 'IGNORE');
                $ins++;
            }
        }

        $status = "Импортирани {$ins} трансакции, пропуснати {$skip}";

        return $status;
    }


    /**
     * Намира съответствията на документи и папки и ги записва в полето `matches`
     */
    public static function findMatches($ids = null, $options = null)
    {
        // Праг за приемане на съответствие - сбор от точки
        $dealsMin = is_numeric($options->dealsMin ?? null) ? $options->dealsMin : 0.5;

        list($documents, $folderIds) = self::getDocuments();
        $folders = self::getFolders();

        $query = self::getQuery();
        $cnt = 0;

        $timeLine = dt::addSecs(-1 * 24 * 60 * 60);

        if (is_array($ids) && countR($ids)) {
            $ids = implode(',', $ids);
            $query->where("#id IN ({$ids})");
        } else {
            $query->where("#state = 'waiting' AND #createdOn > '{$timeLine}'");
        }

        while ($rec = $query->fetch()) {
            $cnt++;
            $contragent = null;

            // Вадим номерата от основанието
            $matches = array();
            preg_match_all('/([1-9][0-9]+)/', $rec->reason, $matches);
            if (countR($matches[0])) {
                $numbers = array_flip($matches[0]);
            } else {
                $numbers = array();
            }

            // Зануляваме само ключовете на това разнасяне - другото остава непокътнато
            $allMatches = $rec->matches['all'] ?? null;
            $rec->matches = array();
            if (isset($allMatches)) {
                $rec->matches['all'] = $allMatches;
            }

            $ourAcc = bank_OwnAccounts::fetch($rec->ownAccountId);

            // Валутата на трансакцията е тази на нашата сметка - импортът отхвърля извлеченията в друга валута
            $transCurrencyCode = self::getCurrencyCodeByAccount($rec->ownAccountId);
            $rates = array();

            // Намираме папката на контрагента по ИБАН-а
            if ($i = $rec->contragentIban) {
                $i = strtoupper(preg_replace('/[^a-z0-9]/i', '', $i));
                $bAcc = bank_Accounts::fetch(array("#iban = '[#1#]'", $i));
                if (!$bAcc) {
                    $bAcc = bank_Accounts::fetch(array("#iban = '#[#1#]'", $i));
                }
                if ($bAcc) {
                    $rec->matches['bAcc'] = $bAcc;

                    // Ако е наша сметката
                    if ($ibanAcc = bank_OwnAccounts::fetch("#bankAccountId = {$bAcc->id}")) {
                        if ($rec->type == 'outgoing') {
                            $rec->matches['folderId'] = $ibanAcc->folderId;
                            $toSave = true;
                        } else {
                            $rec->matches['folderId'] = $ourAcc->folderId;
                            $toSave = true;
                        }
                    } else {
                        $rec->matches['folderId'] = doc_Folders::getIdByCover($bAcc->contragentCls, $bAcc->contragentId);
                    }
                }
            }

            // Намираме папката по името на контрагента
            if (empty($rec->matches['folderId']) && ($contragent = $rec->contragentName)) {
                $contragent = trim(strtolower(self::transliterate(str_replace('.', '', $contragent))));

                if (!empty($folders[$contragent])) {
                    $folderId = $folders[$contragent];
                    $rec->matches['folderId'] = $folderId;
                } else {
                    if ($folderId = self::findFolder($contragent, $folders)) {
                        $rec->matches['folderId'] = $folderId;
                    }
                }
            }

            foreach ($documents as $d) {

                // Ако типа на документа и на плащането не съвпадат - прескачаме
                if ($rec->type != $d->type) {
                    continue;
                }

                // Ако имаме папка, прескачаме документите, които не са в нея
                if (isset($rec->matches['folderId']) && $rec->matches['folderId'] != $d->folderId) {
                    continue;
                }

                // Сумата на документа, приведена към валутата на трансакцията
                $docAmount = $d->amount;

                if (!empty($transCurrencyCode) && !empty($d->currencyCode) && $d->currencyCode != $transCurrencyCode) {
                    $rateKey = $d->currencyCode . '>' . $transCurrencyCode;

                    if (!array_key_exists($rateKey, $rates)) {
                        $rates[$rateKey] = currency_CurrencyRates::getRate($rec->valior, $d->currencyCode, $transCurrencyCode);
                    }

                    // Без валутен курс сумите не са сравними - прескачаме документа
                    if (empty($rates[$rateKey])) {
                        continue;
                    }

                    $docAmount = round($d->amount * $rates[$rateKey], 2);
                }

                $p = 0;
                $pParts = array();

                // Номер на документа
                if (strlen($d->number) && stripos($rec->reason, $d->number) !== false) {
                    $pNumber = max(0, 1 - 2.8 / strlen($d->number));
                    if (!empty($numbers[$d->number])) {
                        $pNumber += max(0, 1 - 1.3 / strlen($d->number));
                    }

                    $p += $pNumber;
                    $pParts['number'] = $pNumber;
                }

                // Сумата на документа
                $maxAmount = max($docAmount, $rec->amount);
                $delta = $maxAmount ? abs($docAmount - $rec->amount) / $maxAmount : 1;
                if ($delta < 0.001) {
                    $p += 0.31;
                    $pParts['amount'] = 0.31;
                } elseif ($delta < 0.03) {
                    $p += 0.11;
                    $pParts['amount'] = 0.11;
                }

                // Дата на докуемнта
                if (!empty($d->date)) {
                    $delta = abs(dt::secsBetween($d->date, $rec->valior));

                    if ($delta <= 24 * 60 * 60) {
                        $p += 0.32;
                        $pParts['date'] = 0.32;
                    } elseif ($delta < 3 * 24 * 60 * 60) {
                        $p += 0.12;
                        $pParts['date'] = 0.12;
                    }
                }

                // Папка на документа
                if (!isset($rec->matches['folderId']) && $p >= 0.4 && $contragent) {
                    list($folderName) = array_keys($folders, $d->folderId);
                    if (self::phraseDistance($folderName, $contragent) > 0.85) {
                        $p += 0.1;
                        $pParts['folder'] = 0.1;
                    } else {
                        $p -= 0.2;
                        $pParts['folder'] = -0.2;
                    }
                }

                if ($p >= $dealsMin) {
                    // Копие, защото масивът с документи е общ за всички трансакции
                    $match = clone $d;
                    $match->p = $p;
                    $match->pParts = $pParts;
                    $match->amountInTransCurrency = $docAmount;

                    $rec->matches['docs'][] = $match;
                }
            }

            // Ако имаме документи, но нямаме папка, опитваме се да я определим от най-вероятните документи
            if (empty($rec->matches['folderId']) && !empty($rec->matches['docs']) && is_array($rec->matches['docs'])) {
                $foldersTmp = array();
                foreach ($rec->matches['docs'] as $d) {
                    $foldersTmp[$d->folderId] = ($foldersTmp[$d->folderId] ?? 0) + $d->p;
                }

                list($rec->matches['folderId']) = array_keys($foldersTmp, max($foldersTmp));

                if ($rec->matches['folderId']) {
                    foreach ($rec->matches['docs'] as $id => $d) {
                        if ($d->folderId != $rec->matches['folderId']) {
                            unset($rec->matches['docs']);
                        }
                    }
                }
            }

            if (!empty($rec->matches['docs']) && is_array($rec->matches['docs'])) {
                foreach ($rec->matches['docs'] as $d) {
                    if (!isset($rec->matches['rows'][$d->threadId])) {
                        $rec->matches['rows'][$d->threadId] = new stdClass();
                    }

                    $row = &$rec->matches['rows'][$d->threadId];
                    if (!isset($row->head[1])) {
                        $row->head[1] = $documents['T' . $d->threadId];
                        $dRec = $row->head[1]->documentMvc->fetch($row->head[1]->documentId);
                        $row->containerId = $dRec->containerId;
                    }

                    switch ($d->documentMvc->className) {
                        case 'sales_Proformas':
                            $row->prof[] = $d;
                            break;
                        case 'sales_Invoices':
                            $row->inv[] = $d;
                            break;
                        case 'bank_IncomeDocuments':
                            $row->bdoc[] = $d;
                            break;
                        default:
                    }
                }
            }


            if (self::isDryRun($options)) {
                self::collectDryRun($options, $rec, array('folderId', 'bAcc', 'docs', 'rows'));
            } else {
                self::save($rec);
            }
        }

        return $cnt;
    }


    /**
     * Намира съответствия между чакащите трансакции и подготвените банкови документи
     *
     * За разлика от `findMatches()`, тук не се тръгва от сделките и техните фактури, а от
     * вече въведените приходни/разходни банкови документи, които чакат активиране.
     * Резултатът се пише в `matches['all']`, без да се пипат ключовете на другото разнасяне.
     *
     * @param string|null   $date    - вальор (един ден); по подразбиране днешният
     * @param stdClass|null $options - onlyWaiting, crossCurrency
     *
     * @return int - брой обработени трансакции
     */
    public static function findMatchesByPendingDocs($date = null, $options = null)
    {
        if (!isset($date)) {
            $date = dt::now(false);
        }

        $crossCurrency = (($options->crossCurrency ?? 'yes') != 'no');

        // Праг за приемане на съответствие по име
        $pendingMin = is_numeric($options->pendingMin ?? null) ? $options->pendingMin : 0.45;

        $where = "DATE(#valior) = '{$date}'";
        if (($options->onlyWaiting ?? 'yes') != 'no') {
            $where = "#state = 'waiting' AND " . $where;
        }

        $query = self::getQuery();
        $recs = $query->fetchAll($where);

        $res = 0;

        if (!countR($recs)) {

            return $res;
        }

        // Имената на папките, в които има чакащи банкови документи
        $allDocs = self::getPendingDocuments($date);
        $folders = $revFolders = array();

        foreach ($allDocs as $folderId => $threads) {
            $fRec = doc_Folders::fetch($folderId);
            if (!$fRec) {
                continue;
            }

            $Cover = cls::get($fRec->coverClass);
            $coverRec = $Cover->fetch($fRec->coverId);
            if (!$coverRec) {
                continue;
            }

            if (strlen($coverRec->name ?? '') > 2) {
                $name = self::transliterate($coverRec->name, true);
                $folders[$folderId] = $name;
                $revFolders[$name] = $folderId;

                // Добавяме и името с "изяден" първи интервал
                list($first, $second) = array_pad(explode(' ', $name, 2), 2, '');
                $revFolders[$first . $second] = $folderId;
            }

            if (strlen($coverRec->folderName ?? '') > 2) {
                $name = self::transliterate($coverRec->folderName, true);
                $revFolders[$name] = $folderId;
            }
        }

        foreach ($recs as $rec) {
            $fixFolderId = null;
            $contragentName = self::transliterate($rec->contragentName, true);
            $currencyId = self::getCurrencyByAccount($rec->ownAccountId);
            $reffs = self::getNumSeqs($rec->reason, 3);

            // Опитваме се да фиксираме папката по IBAN
            if (strlen($rec->contragentIban ?? '') > 5) {
                $i = strtoupper(preg_replace('/[^a-z0-9]/i', '', $rec->contragentIban));
                $bAcc = bank_Accounts::fetch(array("#iban = '[#1#]'", $i));
                if (!$bAcc) {
                    $bAcc = bank_Accounts::fetch(array("#iban = '#[#1#]'", $i));
                }

                if ($bAcc) {
                    $Contragent = cls::get($bAcc->contragentCls);
                    $cRec = $Contragent->fetch($bAcc->contragentId);
                    if (!empty($cRec->folderId)) {
                        $fixFolderId = $cRec->folderId;
                    }
                }
            }

            // Опитваме се да фиксираме папката по пълно съвпадение на имената
            if (!isset($fixFolderId)) {
                $fixFolderId = $revFolders[$contragentName] ?? null;
            }

            if (!isset($rec->matches) || !is_array($rec->matches)) {
                $rec->matches = array();
            }

            $rec->matches['all'] = array();

            // Циклим по всички документи и търсим максимално съвпадение
            foreach ($allDocs as $folderId => $threads) {
                if (isset($fixFolderId)) {
                    if ($fixFolderId != $folderId) {
                        continue;
                    }
                    $sameName = 1;
                } else {
                    $sameName = self::sameNames($contragentName, $folders[$folderId] ?? '');
                }

                if ($sameName >= 0.9) {
                    $rec->matches['all'][$folderId] = array();
                }

                foreach ($threads as $docs) {
                    foreach ($docs as $d) {
                        if ($d->type != $rec->type) {
                            continue;
                        }

                        $sameSum = self::sameSum($currencyId, $rec->amount, $d->currencyId, $d->amount, $date, $crossCurrency);
                        $hasReason = self::sameNumb($reffs, $d->reffs);

                        // или името и сумата съвпадат, или основанието и сумата
                        if ($sameName >= $pendingMin && $sameSum >= 0.9) {
                            $rule = 'name';
                        } elseif ($hasReason >= 0.9 && $sameSum >= 0.9) {
                            $rule = 'reason';
                        } else {
                            continue;
                        }

                        // Копие, защото масивът с документи е общ за всички трансакции
                        $match = clone $d;
                        $match->rule = $rule;
                        $match->sameName = round($sameName, 2);
                        $match->sameSum = round($sameSum, 2);
                        $match->hasReason = round($hasReason, 2);

                        // Оценката е по-слабото от двойката, задействала правилото
                        $match->p = round(min($sameSum, ($rule == 'reason') ? $hasReason : $sameName), 2);

                        $rec->matches['all'][$folderId][] = $match;
                    }
                }
            }

            if (self::isDryRun($options)) {
                self::collectDryRun($options, $rec, array('all'));
            } else {
                self::save($rec, 'matches');
            }

            $res++;
        }

        return $res;
    }


    /**
     * Връща подготвените (чакащи) банкови документи, групирани по папка и нишка
     *
     * @param string $date
     *
     * @return array - [folderId][threadId][] => stdClass
     */
    public static function getPendingDocuments($date)
    {
        $docs = array();

        foreach (array('bank_IncomeDocuments', 'bank_SpendingDocuments') as $mvcName) {
            $Mvc = cls::get($mvcName);
            $query = $Mvc->getQuery();
            $query->where("(#state = 'pending' OR (#state = 'active' AND DATE(#activatedOn) = '{$date}')) AND DATE(#createdOn) <= '{$date}'");
            $query->orderBy('createdOn', 'DESC');

            while ($rec = $query->fetch()) {
                $o = new stdClass();
                $o->type = ($mvcName == 'bank_SpendingDocuments') ? 'outgoing' : 'incoming';
                $o->number = $rec->number ?? null;
                $o->date = !empty($rec->valior) ? $rec->valior : $rec->termDate;
                if (!empty($rec->amount)) {
                    $o->amount = round($rec->amount, 2);
                    $o->currencyId = $rec->currencyId;
                } else {
                    $o->amount = round($rec->amountDeal, 2);
                    $o->currencyId = $rec->dealCurrencyId;
                }

                $o->currencyCode = currency_Currencies::getCodeById($o->currencyId);
                $o->folderId = $rec->folderId;
                $o->threadId = $rec->threadId;
                $o->documentMvc = $mvcName;
                $o->documentId = $rec->id;
                $o->reffs = self::getReffs($rec->threadId, $rec->createdOn);

                $docs[$rec->folderId][$rec->threadId][] = $o;
            }
        }

        return $docs;
    }


    /**
     * Връща номерата, с които документите в нишката могат да бъдат посочени в основанието
     *
     * @param int    $threadId
     * @param string $createdOn
     *
     * @return array
     */
    public static function getReffs($threadId, $createdOn)
    {
        $reff = '';

        foreach (array('sales_Sales', 'purchase_Purchases', 'findeals_Deals') as $mvcName) {
            $Mvc = cls::get($mvcName);
            $rec = $Mvc->fetch("#threadId = {$threadId}");
            if ($rec) {
                $reff = ($rec->reff ?? '') . ' ' . $rec->id;
                break;
            }
        }

        // Търсим фактури и проформи, които са създадени до 72 часа преди този документ
        $before72 = dt::addSecs(-72 * 3600, $createdOn);

        $iQuery = sales_Invoices::getQuery();
        while ($rec = $iQuery->fetch("#threadId = {$threadId} AND #createdOn >= '{$before72}' AND #createdOn <= '{$createdOn}' AND #state = 'active'")) {
            $reff .= ' ' . $rec->number;
        }

        $pQuery = sales_Proformas::getQuery();
        while ($rec = $pQuery->fetch("#threadId = {$threadId} AND #createdOn >= '{$before72}' AND #createdOn <= '{$createdOn}' AND #state = 'active'")) {
            $reff .= ' ' . $rec->number;
        }

        return self::getNumSeqs($reff, 3);
    }


    /**
     * Връща всички числени последователности с дължини между $minLen и $maxLen
     */
    public static function getNumSeqs($str, $minLen = 1, $maxLen = '')
    {
        $matches = $res = array();

        if (preg_match_all("/([0-9]{{$minLen},{$maxLen}})/", (string) $str, $matches)) {
            $res = $matches[0];
        }

        return $res;
    }


    /**
     * Намира достоверността на наличието на еднакъв номер в двата масива
     *
     * @return float 0..1
     */
    public static function sameNumb($arr1, $arr2)
    {
        if (!is_array($arr1) || !is_array($arr2)) {

            return 0;
        }

        $max = 0;

        foreach ($arr1 as $a) {
            foreach ($arr2 as $b) {
                if ($a == $b) {
                    $max = max($max, strlen($a));
                }

                $a1 = (int) $a;
                $b1 = (int) $b;

                if ($a1 == $b1) {
                    $max = max($max, strlen((string) $a1));
                }
            }
        }

        $res = 1 - 1 / ($max + 1);

        return $res;
    }


    /**
     * Доколко са еднакви две имена на фирми
     *
     * @return float 0..1
     */
    public static function sameNames($name1, $name2)
    {
        if (!strlen($name1) || !strlen($name2)) {

            return 0;
        }

        $scale = 10;
        $res = 0;
        $maxLen = max(1, min(strlen($name1), strlen($name2)));

        $trust = 1 - 1 / ($maxLen * $maxLen);

        if ($name1 == $name2) {
            $res = 10;
        } elseif (strpos($name1, $name2) !== false || strpos($name2, $name1) !== false) {
            $res = 5;
        } else {
            $len1 = strlen($name1);
            $len2 = strlen($name2);

            if ($len1 > 4 && $len2 > 4) {
                if (abs($len1 - $len2) < 3) {
                    $lev = levenshtein($name1, $name2);
                    $err = $lev / ($len1 + $len2);

                    if ($err <= 0.05) {
                        $res = 9;
                    } elseif ($err < 0.1) {
                        $res = 6;
                    } elseif ($err < 0.15) {
                        $res = 3;
                    }
                }

                if (!($res > 0)) {
                    $common = 0;
                    $len = min($len1, $len2);

                    for ($i = 0; $i < $len; $i++) {
                        if ($name1[$i] == $name2[$i]) {
                            $common++;
                        } else {
                            break;
                        }
                    }

                    $sim = $common / $len;

                    if ($sim > 0.5) {
                        $res = $sim * $sim * 10;
                    }
                }
            }
        }

        return round($res * $trust / $scale, 2);
    }


    /**
     * Намира доколко са близки две суми в еднакви или различни валути
     *
     * @param int|null $cur1
     * @param float    $sum1
     * @param int|null $cur2
     * @param float    $sum2
     * @param string   $date
     * @param bool     $crossCurrency - да се сравняват ли суми в различни валути
     *
     * @return float 0..1
     */
    public static function sameSum($cur1, $sum1, $cur2, $sum2, $date, $crossCurrency = true)
    {
        $scale = 10;

        if (!empty($cur1) && !empty($cur2) && $cur1 != $cur2) {
            if (!$crossCurrency) {

                return 0;
            }

            $code1 = currency_Currencies::getCodeById($cur1);
            $code2 = currency_Currencies::getCodeById($cur2);

            if (empty($code1) || empty($code2)) {

                return 0;
            }

            $rate = currency_CurrencyRates::getRate($date, $code1, $code2);

            if (empty($rate)) {

                return 0;
            }

            $sum1 *= $rate;
        }

        $res = 0;

        if (round($sum1, 2) == round($sum2, 2)) {
            $res = 10;
        } else {
            $max = abs(max($sum1, $sum2));
            $maxDif = 0.5;
            if ($max >= 100) {
                $maxDif = 1;
            }
            if ($max >= 1000) {
                $maxDif = 5;
            }
            if ($max >= 10000) {
                $maxDif = 10;
            }
            if ($max >= 100000) {
                $maxDif = 50;
            }
            if ($max >= 1000000) {
                $maxDif = 100;
            }

            $dif = abs($sum1 - $sum2);

            if ($dif <= $maxDif / 4) {
                $res = 8;
            } elseif ($dif <= $maxDif / 2) {
                $res = 5;
            } elseif ($dif <= $maxDif) {
                $res = 1;
            }
        }

        return round($res / $scale, 2);
    }


    /**
     * Връща валутата на нашата сметка
     */
    public static function getCurrencyByAccount($ourAccountId)
    {
        if (empty($ourAccountId)) {

            return null;
        }

        $accId = bank_OwnAccounts::fetchField($ourAccountId, 'bankAccountId');

        return bank_Accounts::fetchField($accId, 'currencyId');
    }


    /**
     * Транслитерация по правила UniCredit
     *
     * @param string $string
     * @param bool   $extended - разширена нормализация (правни форми, ET, &);
     *                           ползва се от разнасянето по чакащи банкови документи
     *
     * @return string
     */
    public static function transliterate($string, $extended = false)
    {
        $code['э'] = 'e';
        $code['а'] = 'a';
        $code['б'] = 'b';
        $code['в'] = 'v';
        $code['г'] = 'g';
        $code['д'] = 'd';
        $code['е'] = 'e';
        $code['ж'] = 'zh';
        $code['з'] = 'z';
        $code['и'] = 'i';
        $code['й'] = 'j';
        $code['к'] = 'k';
        $code['л'] = 'l';
        $code['м'] = 'm';
        $code['н'] = 'n';
        $code['о'] = 'o';
        $code['п'] = 'p';
        $code['р'] = 'r';
        $code['с'] = 's';
        $code['т'] = 't';
        $code['у'] = 'u';
        $code['ф'] = 'f';
        $code['х'] = 'h';
        $code['ц'] = 'c';
        $code['ч'] = 'ch';
        $code['ш'] = 'sh';
        $code['щ'] = 'sht';
        $code['ъ'] = 'a';
        $code['ы'] = 'yi';
        $code['ь'] = 'j';
        $code['ю'] = 'yu';
        $code['я'] = 'ya';

        if ($extended) {
            $code['&'] = ' and ';
        }

        $keys = array_keys($code);

        $string = mb_strtolower($string);

        $res = preg_replace('/[^a-z0-9]+/i', ' ', str_replace($keys, $code, $string));

        if (!$extended) {
            $res = str_replace(array(' ood ood', 'ad ad', ' eood eood', 'ead ead'), array(' ood', ' ad', ' eood', ' ead'), $res);

            return $res;
        }

        $res = trim($res);

        // ET винаги да е от края
        if (substr($res, 0, 3) == 'et ') {
            $res = substr($res, 3) . ' et';
        }

        $res = str_replace(array(' ood ood', 'ad ad', ' eood eood', ' ead ead', ' et et'), array(' ood', ' ad', ' eood', ' ead', ' et'), $res);

        $from = array(' s a s ', ' s r l ', ' s r o ', ' s p a ', ' e k ', ' m b h ', ' s a s u ', ' g m b h ', ' a s ', ' balgaria ', 'iks ', 'ics', ' limited');
        $to = array(' sas ', ' srl ', ' sro ', ' spa ', ' ek ', ' mbh ', ' sasu ', ' gmbh ', ' as ', ' bulgaria ', 'ix ', 'ix ', ' ltd');

        $res = trim(str_replace($from, $to, ' ' . $res . ' '));

        return $res;
    }


    /**
     * Връща масив с папки, където може да има плащания
     */
    public static function getFolders($inThePast = null)
    {
        $hnd = 'BANK_FOLDERS_REGISTER';

        if (!$inThePast) {
            $inThePast = 60 * 60 * 24 * 1980;
        }

        $cachedFolders = core_Cache::get('BANK', 'ACTIVE_FOLDERS');

        if (is_array($cachedFolders)) {
            $inThePast = 60 * 60 * 24 * 1;
        } else {
            $cachedFolders = array();
        }

        $query = crm_Companies::getQuery();
        $query->EXT('last', 'doc_Folders', 'externalKey=folderId');
        $query->EXT('coverClass', 'doc_Folders', 'externalKey=folderId');

        $query->where('#coverClass = ' . core_Classes::getId('crm_Companies'));
        $query->where("#state = 'active' OR #state = 'opened'");
        $query->where('#folderId > 0');

        $lastDateActivity = DT::addSecs(-$inThePast);
        $query->where("#last > '{$lastDateActivity}'");

        $res = array();

        while ($rec = $query->fetch()) {
            $title = self::transliterate(str_replace('.', '', $rec->name));
            if (empty($res[$title]) && empty($cachedFolders[$title])) {
                $res[$title] = $rec->folderId;
            }
        }

        $res1 = array_merge($res, $cachedFolders);

        if (countR($res)) {
            core_Cache::set('BANK', 'ACTIVE_FOLDERS', $res1, 24 * 60);
        }

        return $res1;
    }


    /**
     * Връща масив със записи за всички отворени документи
     *
     * @return array
     *               o type         (incoming/outgoing)
     *               o amount       сумата на документа
     *               o currencyCode кода на валутата, в която е `amount`
     *
     */
    public static function getDocuments()
    {
        // Обикаляме по всичко отворени Продажби и такива, в които имаме затваряне
        $earlyClosed = dt::addSecs(-5 * 24 * 60 * 60);
        $threads = $folders = $docs = array();

        $query = sales_Sales::getQuery();
        $query->where("#state = 'active' OR (#state = 'closed' AND #closedOn >= '{$earlyClosed}')");
        $query->orderBy('createdOn', 'DESC');
        while ($rec = $query->fetch()) {
            // Извличаме всички проформи, фактури и документи за плащане в посочените нишки

            $threads[] = $rec->threadId;
            $folders[$rec->folderId] = $rec->folderId;

            $o = new stdClass();
            $o->type = 'incoming';
            $o->number = $rec->id;
            $o->amount = round(($rec->amountBl ? $rec->amountBl : $rec->amountDeal - $rec->amountDiscount + $rec->amountVat) / self::getCurrencyRate($rec), 2);
            $o->currencyId = $rec->currencyId;
            $o->currencyCode = $rec->currencyId;
            $o->folderId = $rec->folderId;
            $o->threadId = $rec->threadId;
            $o->documentMvc = $query->mvc;
            $o->documentId = $rec->id;

            $docs['T' . $rec->threadId] = $o;
        }

        // Обикаляме по всички Финансови сделики
        $query = findeals_Deals::getQuery();
        $query->where("#state = 'active' OR (#state = 'closed' AND #closedOn >= '{$earlyClosed}')");
        $query->orderBy('createdOn', 'DESC');
        while ($rec = $query->fetch()) {
            $threads[] = $rec->threadId;
            $folders[$rec->folderId] = $rec->folderId;

            $o = new stdClass();
            $o->type = $rec->amountDeal > 0 ? 'incoming' : 'outgoing';
            $o->number = $rec->id;
            $o->amount = abs(round(($rec->amountDeal) / self::getCurrencyRate($rec), 2));
            $o->currencyId = $rec->currencyId;
            $o->currencyCode = $rec->currencyId;
            $o->folderId = $rec->folderId;
            $o->threadId = $rec->threadId;
            $o->documentMvc = $query->mvc;
            $o->documentId = $rec->id;

            $docs['T' . $rec->threadId] = $o;
        }

        // Обикаляме по всички Покупки

        $query = purchase_Purchases::getQuery();
        $query->where("#state = 'active' OR (#state = 'closed' AND #closedOn >= '{$earlyClosed}')");
        $query->orderBy('createdOn', 'DESC');
        while ($rec = $query->fetch()) {
            $threads[] = $rec->threadId;
            $folders[$rec->folderId] = $rec->folderId;

            $o = new stdClass();
            $o->type = 'outgoing';
            $o->number = $rec->id;
            $o->amount = round(($rec->amountBl ? $rec->amountBl : $rec->amountDeal - $rec->amountDiscount + $rec->amountVat) / self::getCurrencyRate($rec), 2);
            $o->currencyId = $rec->currencyId;
            $o->currencyCode = $rec->currencyId;
            $o->folderId = $rec->folderId;
            $o->threadId = $rec->threadId;
            $o->documentMvc = $query->mvc;
            $o->documentId = $rec->id;

            $docs['T' . $rec->threadId] = $o;
        }

        // Без нишки няма къде да търсим фактури и проформи, а `#threadId IN ()` е невалиден SQL
        if (!countR($threads)) {

            return array($docs, $folders);
        }

        $threadIds = implode(',', $threads);

        $query = sales_Invoices::getQuery();
        $query->orderBy('createdOn', 'DESC');
        while ($rec = $query->fetch("#threadId IN ({$threadIds}) AND #state = 'active'")) {
            $o = new stdClass();
            $o->type = 'incoming';
            $o->number = $rec->number;
            $o->date = $rec->dueDate;
            $o->amount = round($rec->dealValue - $rec->discountAmount + $rec->vatAmount, 2);
            $o->currencyId = $rec->currencyId;
            $o->currencyCode = acc_Periods::getBaseCurrencyCode($rec->date);
            $o->folderId = $rec->folderId;
            $o->threadId = $rec->threadId;
            $o->documentMvc = $query->mvc;
            $o->documentId = $rec->id;
            $docs[] = $o;
        }

        $query = sales_Proformas::getQuery();
        $query->orderBy('createdOn', 'DESC');
        while ($rec = $query->fetch("#threadId IN ({$threadIds}) AND #state = 'active'")) {
            $o = new stdClass();
            $o->type = 'incoming';
            $o->number = $rec->number;
            $o->date = $rec->dueDate;
            $o->amount = round($rec->dealValue - $rec->discountAmount + $rec->vatAmount, 2);
            $o->currencyId = $rec->currencyId;
            $o->currencyCode = acc_Periods::getBaseCurrencyCode($rec->date);
            $o->folderId = $rec->folderId;
            $o->threadId = $rec->threadId;
            $o->documentMvc = $query->mvc;
            $o->documentId = $rec->id;
            $docs[] = $o;
        }

        // Входящи банкови документи
        $query = bank_IncomeDocuments::getQuery();
        $query->orderBy('createdOn', 'DESC');
        while ($rec = $query->fetch("#threadId IN ({$threadIds}) AND (#state = 'active' OR #state = 'pending')")) {
            $o = new stdClass();
            $o->type = 'incoming';
            $o->number = $rec->number;
            $o->date = $rec->valior ? $rec->valior : $rec->termDate;
            $o->amount = round($rec->amountDeal, 2);
            $o->currencyId = $rec->currencyId;
            $o->currencyCode = currency_Currencies::getCodeById($rec->dealCurrencyId);
            $o->folderId = $rec->folderId;
            $o->threadId = $rec->threadId;
            $o->documentMvc = $query->mvc;
            $o->documentId = $rec->id;
            $docs[] = $o;
        }

        return array($docs, $folders);
    }


    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if (haveRole('admin, ceo, bank')) {
            $data->toolbar->addBtn('Разнасяне', array($mvc, 'Match', 'ret_url' => true), 'ef_icon=img/16/briefcase.png, title=Намиране на съответствия');
        }
    }


    /**
     * Разстояние между фрази, без значение на подредбата на думите в тях
     */
    public static function phraseDistance($s1, $s2)
    {
        if (is_array($s1)) {
            $s1Arr = $s1;
        } else {
            $s1Arr = explode(' ', strtolower(trim(preg_replace('/[^a-z0-9]+/i', ' ', $s1))));
        }
        $s2Arr = explode(' ', strtolower(trim(preg_replace('/[^a-z0-9]+/i', ' ', $s2))));

        $s = 0;
        foreach ($s1Arr as $w1) {
            $m = 0;
            foreach ($s2Arr as $w2) {
                $m = max($m, 1 - levenshtein($w1, $w2) / max(strlen($w1), strlen($w2)));
                $res1[$w1][$w2] = $m;
            }
            $s += $m;
        }

        $res = $s / countR($s1Arr);

        return $res;
    }


    /**
     * Връща валутния курс
     */
    public static function getCurrencyRate($rec)
    {
        if (!empty($rec->currencyRate)) {

            return $rec->currencyRate;
        }

        if (!empty($rec->currencyId)) {
            $rate = currency_CurrencyRates::getRate($rec->createdOn, $rec->currencyId, null);

            if ($rate) {

                return $rate;
            }
        }

        return 1;
    }


    /**
     * Намира най-близката папка
     */
    public static function findFolder($name, $folders)
    {
        if (!strlen($name)) {

            return;
        }

        // Разбиваме името на парчета
        $parts = explode(' ', $name);

        list($longPart) = self::findLongString($parts);

        $id = null;
        $bestRate = 0;
        $bestId = null;

        foreach ($folders as $title => $id) {
            if (strpos($title, $longPart) !== false) {
                $rate = self::phraseDistance($parts, $title);
                if ($rate > $bestRate && $rate > 0.85) {
                    $bestRate = $rate;
                    $bestId = $id;
                }
            }
        }

        return $bestId;
    }


    public static function findLongString($array)
    {
        $mapping = array_combine($array, array_map('strlen', $array));

        return array_keys($mapping, max($mapping));
    }


    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        // Подготовка на филтъра

        $data->query->orderBy('#valior=DESC,ownAccountId,id');
    }


    /**
     * Екшън за намиране на съответствията между трансакциите от извлеченията и документите/папките
     */
    public function act_Match()
    {
        requireRole('admin,ceo,bank');

        $form = cls::get('core_Form');
        $form->title = 'Разнасяне на банковите трансакции';
        $form->info = "<div style='padding:5px'><b>" . tr('Сделки и фактури') . '</b> - ' .
            tr('търси в отворените сделки и техните фактури, проформи и платежни документи') . '.<br><b>' .
            tr('Чакащи банкови документи') . '</b> - ' .
            tr('търси във вече въведените приходни и разходни банкови документи, които чакат активиране') . '.</div>';

        $form->FLD('method', 'enum(both=И двата,deals=Сделки и фактури,pending=Чакащи банкови документи)', 'caption=Алгоритъм->Избор,maxRadio=3,mandatory,silent,removeAndRefreshForm=crossCurrency|dealsMin|pendingMin');
        $form->FLD('period', 'enum(day=Ден (по вальор),range=Период (по вальор),last24=Последните 24 часа (по въвеждане))', 'caption=Обхват->Период,maxRadio=3,mandatory,silent,removeAndRefreshForm=fromDate|toDate');
        $form->FLD('fromDate', 'date', 'caption=Обхват->Дата,silent');
        $form->FLD('toDate', 'date', 'caption=Обхват->До,silent');
        $form->FLD('onlyWaiting', 'enum(yes=Само чакащите,no=Всички)', 'caption=Обхват->Състояние,maxRadio=2,mandatory');
        $form->FLD('crossCurrency', 'enum(yes=Да,no=Не)', 'caption=Чакащи документи->Различни валути,maxRadio=2,mandatory');
        $form->FLD('dealsMin', 'double(min=0,max=3,decimals=2)', 'caption=Прагове->Сделки и фактури,mandatory,hint=Минимален сбор точки за приемане на съответствие');
        $form->FLD('pendingMin', 'double(min=0,max=1,decimals=2)', 'caption=Прагове->Чакащи документи,mandatory,hint=Минимално съвпадение на името за приемане на съответствие');

        $form->setDefault('method', 'both');
        $form->setDefault('period', 'day');
        $form->setDefault('onlyWaiting', 'yes');
        $form->setDefault('crossCurrency', 'yes');
        $form->setDefault('dealsMin', 0.5);
        $form->setDefault('pendingMin', 0.45);
        $form->setDefault('fromDate', Request::get('d', 'date') ?: dt::today());

        // Полетата за обхват зависят от избрания период, а валутите - от избрания алгоритъм
        $form->input('method,period', 'silent');

        if (($form->rec->period ?? 'day') == 'last24') {
            $form->setField('fromDate', 'input=none');
            $form->setField('toDate', 'input=none');
        } elseif (($form->rec->period ?? 'day') == 'range') {
            $form->setField('fromDate', 'caption=Обхват->От,mandatory');
            $form->setField('toDate', 'mandatory');
        } else {
            $form->setField('fromDate', 'mandatory');
            $form->setField('toDate', 'input=none');
        }

        if (($form->rec->method ?? 'both') == 'deals') {
            $form->setField('crossCurrency', 'input=none');
            $form->setField('pendingMin', 'input=none');
        } elseif (($form->rec->method ?? 'both') == 'pending') {
            $form->setField('dealsMin', 'input=none');
        }

        $form->input();

        $preview = '';

        if ($form->isSubmitted()) {
            $rec = $form->rec;

            if ($rec->period == 'range' && !empty($rec->fromDate) && !empty($rec->toDate) && $rec->fromDate > $rec->toDate) {
                $form->setError('fromDate,toDate', 'Началната дата е след крайната');
            }

            if (!$form->gotErrors()) {
                core_App::setTimeLimit(300);

                // Пробното пускане само показва какво би се получило, без да пипа данните
                $rec->dryRun = ($form->cmd == 'preview') ? 'yes' : 'no';

                $res = self::matchByOptions($rec);

                if ($rec->dryRun == 'yes') {
                    $this->logInfo('Пробно разнасяне на банкови трансакции');
                    $preview = $this->renderDryRun($res->recs);
                } else {
                    $this->logWrite('Разнасяне на банкови трансакции');

                    followRetUrl(array($this), "|Разнесени по сделки и фактури|*: {$res->deals}; |по чакащи банкови документи|*: {$res->pending}");
                }
            }
        }

        $form->toolbar->addSbBtn('Разнасяне', 'save', 'ef_icon=img/16/briefcase.png, title=Намиране и записване на съответствията');
        $form->toolbar->addSbBtn('Пробно', 'preview', 'ef_icon=img/16/find.png, title=Показва какво би се разнесло, без да записва');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon=img/16/close-red.png, title=Прекратяване на действията');

        $tpl = $form->renderHtml();

        if (!empty($preview)) {
            $tpl->append($preview);
        }

        return $this->renderWrapping($tpl);
    }


    /**
     * Изпълнява избраните от формата алгоритми за разнасяне
     *
     * @param stdClass $options - method, period, fromDate, toDate, onlyWaiting, crossCurrency,
     *                           dealsMin, pendingMin, dryRun
     *
     * @return stdClass - deals, pending, recs (намереното при пробно пускане)
     */
    public static function matchByOptions($options)
    {
        // Клонираме, за да не пишем резултата в записа на формата
        $options = clone $options;
        $options->result = array();

        $res = (object) array('deals' => 0, 'pending' => 0, 'recs' => array());

        $method = $options->method ?? 'both';

        // Разнасяне по сделките и техните фактури, проформи и платежни документи
        if ($method == 'both' || $method == 'deals') {
            if (($options->period ?? null) == 'last24') {
                $res->deals = self::findMatches(null, $options);
            } else {
                $ids = self::getRecIdsInPeriod($options);
                $res->deals = countR($ids) ? self::findMatches($ids, $options) : 0;
            }
        }

        // Разнасяне по подготвените (чакащи) банкови документи
        if ($method == 'both' || $method == 'pending') {
            foreach (self::getDatesInPeriod($options) as $date) {
                $res->pending += self::findMatchesByPendingDocs($date, $options);
            }
        }

        $res->recs = $options->result;

        return $res;
    }


    /**
     * Пробно ли е пускането - тогава нищо не се записва
     *
     * @param stdClass|null $options
     *
     * @return bool
     */
    private static function isDryRun($options)
    {
        return !empty($options->dryRun) && $options->dryRun != 'no';
    }


    /**
     * Събира намереното при пробно пускане, вместо да го записва
     *
     * Всеки алгоритъм пише в свои ключове на `matches`, затова се пренасят само те - иначе
     * вторият алгоритъм би върнал в резултата остатъци от предишно, вече записано разнасяне.
     *
     * @param stdClass $options - опциите на разнасянето, в тях е и резултатът
     * @param stdClass $rec     - записът с намерените съответствия
     * @param array    $keys    - кои ключове на `matches` идват от този алгоритъм
     *
     * @return void
     */
    private static function collectDryRun($options, $rec, $keys)
    {
        if (!isset($options->result[$rec->id])) {
            $copy = clone $rec;
            $copy->matches = array();
            $options->result[$rec->id] = $copy;
        }

        foreach ($keys as $key) {
            if (isset($rec->matches[$key])) {
                $options->result[$rec->id]->matches[$key] = $rec->matches[$key];
            } else {
                unset($options->result[$rec->id]->matches[$key]);
            }
        }
    }


    /**
     * Рендира какво би се разнесло при пробно пускане
     *
     * @param array $recs
     *
     * @return string
     */
    private function renderDryRun($recs)
    {
        $maxRows = 200;
        $count = countR($recs);
        $withMatches = $shown = 0;
        $rows = '';

        foreach ($recs as $rec) {
            $hasMatch = !empty($rec->matches['docs']) || !empty($rec->matches['all']) || !empty($rec->matches['folderId']);
            if ($hasMatch) {
                $withMatches++;
            }

            if ($shown >= $maxRows) {
                continue;
            }
            $shown++;

            $row = $this->recToVerbal($rec);
            $info = !empty($row->info) ? $row->info : '<i>' . tr('няма съвпадение') . '</i>';
            $account = bank_OwnAccounts::getTitleById($rec->ownAccountId);

            $rows .= '<tr><td>' . ($row->valior ?? '') . "<br><small>{$account}</small></td>" .
                "<td style='text-align:right'>" . ($row->amount ?? '') . '</td>' .
                '<td>' . ($row->contragentName ?? '') . '</td>' .
                "<td>{$info}</td></tr>";
        }

        $head = '<h3>' . tr('Пробно пускане') . '</h3><p>' . tr('Прегледани') . ": {$count}, " .
            tr('с предложения') . ": {$withMatches}. <b>" . tr('Нищо не е записано') . '.</b></p>';

        if ($count > $shown) {
            $head .= '<p><i>' . tr('Показани са само първите') . " {$shown} " . tr('записа') . '.</i></p>';
        }

        if (!$shown) {

            return "<div style='margin:10px'>{$head}</div>";
        }

        return "<div style='margin:10px'>{$head}<table class='listTable' style='font-size:0.9em'>" .
            '<tr><th>' . tr('Вальор') . '</th><th>' . tr('Сума') . '</th><th>' . tr('Контрагент') .
            '</th><th>' . tr('Осчетоводяване') . "</th></tr>{$rows}</table></div>";
    }


    /**
     * Границите на периода, зададен от формата
     *
     * @return array - от, до
     */
    private static function getPeriodBounds($options)
    {
        $from = !empty($options->fromDate) ? $options->fromDate : dt::today();
        $to = (($options->period ?? null) == 'range' && !empty($options->toDate)) ? $options->toDate : $from;

        if ($to < $from) {
            list($from, $to) = array($to, $from);
        }

        return array($from, $to);
    }


    /**
     * Id-та на трансакциите, попадащи в зададения от формата обхват
     *
     * @return array
     */
    private static function getRecIdsInPeriod($options)
    {
        $query = self::getQuery();
        $query->show('id');

        if (($options->onlyWaiting ?? 'yes') != 'no') {
            $query->where("#state = 'waiting'");
        }

        if (($options->period ?? null) == 'last24') {
            $timeLine = dt::addSecs(-1 * 24 * 60 * 60);
            $query->where("#createdOn > '{$timeLine}'");
        } else {
            list($from, $to) = self::getPeriodBounds($options);
            $query->where("#valior >= '{$from}' AND #valior <= '{$to}'");
        }

        $ids = array();
        while ($rec = $query->fetch()) {
            $ids[$rec->id] = $rec->id;
        }

        return $ids;
    }


    /**
     * Дните, за които да се търсят съответствия по чакащите банкови документи
     *
     * @return array
     */
    private static function getDatesInPeriod($options)
    {
        $res = array();

        // При "последните 24 часа" взимаме вальорите на въведените в този интервал трансакции
        if (($options->period ?? null) == 'last24') {
            $query = self::getQuery();
            $query->show('valior');

            if (($options->onlyWaiting ?? 'yes') != 'no') {
                $query->where("#state = 'waiting'");
            }

            $timeLine = dt::addSecs(-1 * 24 * 60 * 60);
            $query->where("#createdOn > '{$timeLine}'");

            while ($rec = $query->fetch()) {
                if (!empty($rec->valior)) {
                    $res[$rec->valior] = $rec->valior;
                }
            }

            return $res;
        }

        list($from, $to) = self::getPeriodBounds($options);

        $date = $from;
        $maxDays = 366;

        while ($date <= $to && $maxDays--) {
            $res[$date] = $date;
            $date = dt::addDays(1, $date, false);
        }

        return $res;
    }
}
