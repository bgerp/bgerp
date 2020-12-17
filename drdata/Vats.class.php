<?php


/**
 * Клас 'drdata_Vats' -
 *
 *
 * @category  vendors
 * @package   drdata
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class drdata_Vats extends core_Manager
{
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Sorting,drdata_Wrapper,plg_RowTools2';
    
    
    /**
     * Не е VAT номер, дори не е ЕИК
     * color:red
     */
    const statusNotVat = 'not_vat';
    
    
    /**
     * Това е ЕИК
     * color:red
     */
    const statusBulstat = 'bulstat';
    
    
    /**
     * Това е VAT номер, но с невалиден синтаксис
     * color:red
     */
    const statusSyntax = 'syntax';
    
    
    /**
     * Това е VAT номер с правилен синтаксис, но не е известно дали е валиден
     * color:green
     */
    const statusUnknown = 'unknown';
    
    
    /**
     * Това е VAT номер с правилен синтаксис, но не е валиден
     * color:red
     */
    const statusInvalid = 'invalid';
    
    
    /**
     * Това е валиден VAT номер
     * color:black
     */
    const statusValid = 'valid';
    
    
    /**
     * Колко най-много vat номера да бъдат обновени след залез?
     */
    const MAX_CNT_VATS_FOR_UPDATE = 1;
    
    
    /**
     * Колко най-много vat номера (по cron) да бъдат обновени след залез?
     */
    const CRON_MAX_CNT_VATS_FOR_UPDATE = 5;
    
    
    /**
     * След колко време да се проверяват unknown статусите
     * 24*60*60 - 1 ден
     */
    public static $unknowTTL = 86400;
    
    
    /**
     * Заглавие
     */
    public $title = 'Регистър на данъчните номера';
    
    
    /**
     * Кой  може да пише?
     */
    public $canWrite = 'admin';
    
    
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Списък с VAT номера, които трябва да се обновят на shutdown
     */
    public $updateOnShutdown = array();
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('vat', 'drdata_vatType(64)', 'caption=VAT');
        $this->FLD('status', 'enum(not_vat,bulstat,syntax,unknown,valid,invalid)', 'caption=Състояние,input=none');
        $this->FLD('lastChecked', 'datetime(format=smartTime)', 'caption=Проверен на,input=none');
        $this->FLD('lastUsed', 'datetime(format=smartTime)', 'caption=Използван на,input=none');
        $this->FLD('info', 'varchar', 'caption=Информация');
        
        $this->setDbUnique('vat');
    }
    
    
    /**
     * @todo Проверява за съществуващ VAT номер
     */
    public function act_Check()
    {
        requireRole('admin');

        $form = cls::get('core_Form');
        $form->title = 'Проверка на VAT номер';
        $form->FNC('vat', 'varchar(32)', 'caption=VAT номер,input');
        $form->toolbar->addSbBtn('Провери');
        $form->input();
        
        if ($form->isSubmitted()) {
            if (!(strlen($vat = core_Type::escape(trim($form->input()->vat))))) {
                $res = new Redirect(array($this, 'Check'), '|Не сте въвели VAT номер');
            } else {
                list($status, ) = $this->check($vat, true);
                switch ($status) {
                    case 'valid':
                        $res = new Redirect(array($this), "|VAT номера|* <i>'{$vat}'</i> |е валиден|*");
                        break;
                    case 'bulstat':
                        $res = new Redirect(array($this), "|Номера|* <i>'{$vat}'</i> |е валиден БУЛСТАТ/ЕИК|*");
                        break;
                    case 'syntax':
                        $res = new Redirect(array($this), "|VAT номера|* <i>'{$vat}'</i> |е синтактично грешен|*");
                        break;
                    case 'invalid':
                        $res = new Redirect(array($this), "|VAT номера|* <i>'{$vat}'</i> |е невалиден|*");
                        break;
                    case 'unknown':
                        $res = new Redirect(array($this), "|Не може да се определи статуса на VAT номера|* <i>'{$vat}'</i>");
                        break;
                    case 'not_vat':
                        $res = new Redirect(array($this), "|Това не е VAT номер|* - <i>'{$vat}'</i>");
                        break;
                    default: expect(false);
                }
            }
            
            return $res;
        }
        
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Генерира бутон, който препраща в страница за проверка на VAT номер
     */
    public static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $data->toolbar->addBtn('Проверка на VAT номер', array($mvc, 'Check'));
    }
    
    
    /**
     * Подреждане - първо новите
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('lastChecked', 'DESC');
    }
    
    
    /**
     * Пълна проверка на VAT номер - синтактична + онлайн проверка.
     *
     * @param string $vat
     * @param bool   $force
     *
     * @return string 'syntax', 'valid', 'invalid', 'unknown'
     */
    public function check($vat, $force = false)
    {
        $canonocalVat = $this->canonize($vat);
        
        $rec = $this->fetch(array("#vat = '[#1#]'", $canonocalVat));
        
        if (!$rec) {
            // Ако нямаме кеширан запис за този VAT номер, създаваме нов и го записваме
            $rec = new stdClass();
            list($rec->status, $rec->info) = $this->checkStatus($canonocalVat);
            $rec->vat = $canonocalVat;
            $rec->lastUsed = $rec->lastChecked = dt::verbal2mysql();
            if (in_array($rec->status, array('valid', 'invalid', 'unknown'))) {
                $this->save($rec, NULL, 'IGNORE');
            }
        } else {
            // Проверяваме дали кеша не е изтекъл
            $expDate = dt::subtractSecs(drdata_Setup::get('VAT_TTL'));
            $lastUsedExp = dt::subtractSecs(drdata_Setup::get('LAST_USED_EXP'));
            $expUnknown = dt::subtractSecs(self::$unknowTTL);
            
            $rec->lastUsed = dt::verbal2mysql();
            $this->save($rec, 'lastUsed');
            
            // Ако информацията за данъчния номер е остаряла или той е неизвестен и не сме го проверявали последните 24 часа
            if ($force || ((($rec->lastChecked <= $expDate) && ($rec->lastUsed >= $lastUsedExp)) || ($rec->status == self::statusUnknown && $rec->lastChecked < $expUnknown))) {
                
                // Ако не е достигнат максимума, добавяме и този запис за обновяване
                if (countR($this->updateOnShutdown) < self::MAX_CNT_VATS_FOR_UPDATE) {
                    $this->updateOnShutdown[] = $rec;
                }
            }
        }
        
        return array($rec->status, $rec->info);
    }
    
    
    /**
     * Проверка за валидността на VAT номер, включително и чрез сървиз на EC
     *
     * @param string $vat Каноничен ват
     *
     * @return string 'valid', 'invalid', 'unknown'
     */
    public function checkStatus($vat)
    {
        // Ако номера не е VAT, тогава само проверяваме, дали не е валиден BULSTAT
        if (!$this->isHaveVatPrefix($vat)) {
            if (self::isBulstat($vat)) {
                $res = self::statusBulstat;
            } else {
                $res = self::statusNotVat;
            }
        }
        
        // Ако синтаксиса не отговаря на VAT, статуса сигнализира за това
        if (!$res && !$this->checkSyntax($vat)) {
            $res = self::statusSyntax;
        }
      
        if (!$res) {
            // Конвериране на български 13-цифрени данъчни номера към 9-цифрени
            if ((strpos($vat, 'BG')) === 0 && (strlen($vat) == 15)) {
                $vat = substr($vat, 0, 11);
            }
            
            $countryCode = substr($vat, 0, 2);
            $vatNumber = substr($vat, 2);
            
            try {

//                 ini_set("default_socket_timeout", 5);
                
                $client = @new SoapClient('http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl', array('connection_timeout' => 4));
                
                $params = array('countryCode' => $countryCode, 'vatNumber' => $vatNumber);
                $result = @$client->checkVat($params);
            } catch (Exception $e) {
                reportException($e);
                $result = new stdClass();
            } catch (Throwable $t) {
                reportException($t);
                $result = new stdClass();
            }
            
            $res = self::statusUnknown;
            
            if (is_object($result)) {
                if ($result->valid === true) {
                    $res = self::statusValid;
                    $info = $result->name . "\n" . $result->address;
                } elseif ($result->valid === false) {
                    $res = self::statusInvalid;
                }
            }
        }
        
        return array($res, $info, $result->name, $result->address);
    }
    
    
    /**
     * Обновяване на статуса на VAT номера след залез
     */
    public static function on_Shutdown($mvc)
    {
        foreach ($mvc->updateOnShutdown as $rec) {
            list($rec->status, $rec->info) = $mvc->checkStatus($rec->vat);
            $rec->lastChecked = dt::verbal2mysql();
            $mvc->save($rec, 'status, info, lastChecked');
        }
    }
    
    
    /**
     * Проверява дали номерът започва с префикс, като за VAT
     */
    public static function isHaveVatPrefix($value)
    {
        $vatPrefixes = arr::make('BE,BG,CY,CZ,DK,EE,EL,DE,PT,FR,FI,HR,HU,LU,MT,SI,IE,IT,LV,LT,NL,PL,SK,RO,SE,ES,GB,AT', true);
        
        if ($vatPrefixes[substr(strtoupper(trim($value)), 0, 2)]) {
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Синтактична валидация на VAT номер от Европейския съюз
     *
     * @see http://php.net/manual/de/function.preg-match.php
     *
     * @param int $vat VAT number to test e.g. GB123 4567 89
     *
     * @return int -1 if country not included OR 1 if the VAT Num matches for the country OR 0 if no match
     */
    public function checkSyntax(&$vat)
    {
        switch (strtoupper(substr($vat, 0, 2))) {
            case 'AT':
                $regex = '/^ATU[0-9]{8}$/i';
                break;
            case 'BE':
                $regex = '/^BE[0]{0,1}[0-9]{9}$/i';
                break;
            case 'BG':
                if (strlen($vat) == 15) {
                    $vat = substr($vat, 0, 11);
                }
                
                $regex = '/^BG[0-9]{9,10}$/i';
                break;
            case 'CY':
                $regex = '/^CY[0-9]{8}[A-Z]$/i';
                break;
            case 'CZ':
                $regex = '/^CZ[0-9]{8,10}$/i';
                break;
            case 'DK':
                $regex = '/^DK([0-9]{2}[\ ]{0,1}){3}[0-9]{2}$/i';
                break;
            case 'EE':
            case 'DE':
            case 'PT':
            case 'EL':
                $regex = '/^(EE|EL|DE|PT)[0-9]{9}$/i';
                break;
            case 'FR':
                $regex = '/^FR[0-9A-Z]{2}[\ ]{0,1}[0-9]{9}$/i';
                break;
            case 'FI':
            case 'HU':
            case 'LU':
            case 'MT':
            case 'SI':
                $regex = '/^(FI|HU|LU|MT|SI)[0-9]{8}$/i';
                break;
            case 'HR':
                $regex = '/^HR(\d{11})$/';
                break;
            case 'IE':
                $regex = '/^IE[0-9][0-9A-Z\+\*][0-9]{5}[A-Z]{1,2}$/i';
                break;
            case 'IT':
            case 'LV':
                $regex = '/^(IT|LV)[0-9]{11}$/i';
                break;
            case 'LT':
                $regex = '/^LT([0-9]{9}|[0-9]{12})$/i';
                break;
            case 'NL':
                $regex = '/^NL[0-9]{9}B[0-9]{2}$/i';
                break;
            case 'PL':
            case 'SK':
                $regex = '/^(PL|SK)[0-9]{10}$/i';
                break;
            case 'RO':
                $regex = '/^RO[0-9]{2,10}$/i';
                break;
            case 'SE':
                $regex = '/^SE[0-9]{12}$/i';
                break;
            case 'ES':
                $regex = '/^ES([0-9A-Z][0-9]{7}[A-Z])|([A-Z][0-9]{7}[0-9A-Z])$/i';
                break;
            case 'GB':
                $regex = '/^GB([1-9][0-9]{2}[\ ]{0,1}[0-9]{4}[\ ]{0,1}[0-9]{2})|([1-9][0-9]{2}[\ ]{0,1}[0-9]{4}[\ ]{0,1}[0-9]{2}[\ ]{0,1}[0-9]{3})|((GD|HA)[0-9]{3})$/i';
                break;
            default:
                
                return false;
        }
        
        return preg_match($regex, $vat);
    }
    
    
    /**
     * Връща каноническото представяне на VAT номер - големи букви, без интервали.
     *
     * @param string $vat
     */
    public function canonize($vat)
    {
        $canonicalVat = preg_replace('/[^a-z\d]/i', '', $vat);
        $canonicalVat = strtoupper($canonicalVat);
        
        return $canonicalVat;
    }
    
    
    /**
     * Проверява дали това е валиден български булстат
     */
    public static function isBulstat($inBULSTAT)
    {
        for ($i = 0 ; $i <= strlen($inBULSTAT); $i++) {
            $c = substr($inBULSTAT, $i, 1);
            
            if ($c >= '0' && $c <= '9') {
                $BULSTAT .= $c;
            }
        }
        
        switch (strlen($BULSTAT)) {
            case 9:
                for ($i = 0; $i < 8; $i++) {
                    $c = (int) $c + ((int) substr($BULSTAT, $i, 1)) * ($i + 1);
                }
                $c = $c % 11;
                
                if ($c == 10) {
                    $c = 0;
                    
                    for ($i = 0; $i < 8; $i++) {
                        $c = $c + ((int) substr($BULSTAT, $i, 1)) * ($i + 3);
                    }
                    $c = ($c % 11) % 10;
                }
                
                return (int) substr($BULSTAT, 8, 1) == $c;
            
            case 10:
                
                /*
                 * За данъчен номер:
                 * първите 9 цифри се умножават съответно по тези множители:
                 * 4 3 2 7 6 5 4 3 2, Контролната цифра е равна на 11 минус остатъка
                 *  на сбора разделен на 11. Ако контролната цифра е 10 - се приема за 0.
                 */
                $v = array(4, 3, 2, 7, 6, 5, 4, 3, 2);
                $c = 0;
                
                for ($i = 0; $i < 9; $i++) {
                    $currentChar = ((int) substr($BULSTAT, $i, 1));
                    $c = $c + $currentChar * $v[$i];
                }
                $c = 11 - ($c % 11);
                $c = ($c == 10) ? 0 : $c;
                
                $lastDigit = ((int) substr($BULSTAT, 9, 1));
                
                return $lastDigit == $c;
                
            case 13:
                
               
                $v1 = array(2, 7, 3, 5);
                $v2 = array(4, 9, 5, 7);
                
                for ($i = 8; $i < 12; $i++) {
                    $c = $c + ((int) substr($BULSTAT, $i, 1)) * $v1[$i - 8] ;
                }
                $c = $c % 11;
                
                if ($c == 10) {
                    $c = 0;
                    
                    for ($i = 8; $i < 12; $i++) {
                        $c = $c + ((int) substr($BULSTAT, $i, 1)) * $v2[$i - 8];
                    }
                    $c = ($c % 11) % 10;
                }
                
                return ((int) substr($BULSTAT, 12, 1) == $c) && drdata_Vats::isBULSTAT(substr($BULSTAT, 0, 9)) ;
        }
        
        return false;
    }
    
    
    /**
     * Функция връщаща ЕИК то по зададен VAT номер, Ако е подаден ЕИК се връща
     * директно
     *
     * @param string $vatNo - Ват номер
     *
     * @return string - ЕИК номера извлечен от Ват-а, или ако е ЕИК
     *                директно го връща
     */
    public static function getUicByVatNo($vat)
    {
        $self = cls::get(get_called_class());
        
        $vat = $self->canonize($vat);
        
        if (substr($vat, 0, 2) == 'BG') {
            $uic = substr($vat, 2);
        }
        
        return $uic;
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->FNC('vatNum', 'varchar', 'caption=VAT номер, input');
        $data->listFilter->showFields = 'vatNum';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->input('vatNum');
        
        if ($data->listFilter->rec->vatNum) {
            $data->query->like('vat', $data->listFilter->rec->vatNum);
        }
        
        // Сортиране на записите по num
        $data->query->orderBy('lastChecked', 'DESC');
        $data->query->orderBy('lastUsed', 'DESC');
        $data->query->orderBy('vat');
    }
    
    
    /**
     * Извиква се от крона. Премахва старите статус съобщения
     */
    public function cron_checkVats()
    {
        // За да се стартира on_ShutDown
        cls::get(get_called_class());
        
        $expDate = dt::subtractSecs(drdata_Setup::get('VAT_TTL'));
        $lastUsedExp = dt::subtractSecs(drdata_Setup::get('LAST_USED_EXP'));
        $unknownExpDate = dt::subtractSecs(self::$unknowTTL);
        
        $statusUnknown = self::statusUnknown;
        
        $query = $this->getQuery();
        $query->where("#lastChecked <= '{$expDate}'");
        $query->where("#lastUsed >= '{$lastUsedExp}'");
        $query->orWhere("#status = '{$statusUnknown}' AND #lastChecked <= '{$unknownExpDate}'");
        
        $query->limit(self::CRON_MAX_CNT_VATS_FOR_UPDATE);
        
        $query->orderBy('lastChecked', 'ASC');
        
        while ($rec = $query->fetch()) {
            $this->updateOnShutdown[] = $rec;
        }
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    public static function on_AfterSetupMVC($mvc, &$res)
    {
        // Данни за работата на cron
        $rec = new stdClass();
        $rec->systemId = 'checkVats';
        $rec->description = 'Проверка на VAT номера';
        $rec->controller = $mvc->className;
        $rec->action = 'checkVats';
        $rec->period = 10;
        $rec->offset = rand(0, 8);
        $rec->isRandOffset = true;
        $rec->delay = 0;
        $rec->timeLimit = 200;
        $res .= core_Cron::addOnce($rec);
    }
    
    
    /**
     * Връща данните от търговския регистър по зададения БГ ддс номер или ЕИК
     *
     * @param string $eik - ЕИК номер
     *
     * @return false|stdClass - обект с данни или false, ако не намери нищо
     *                        o name    - име
     *                        o country - ид на държава
     *                        o pCode   - пощенски код
     *                        o place   - населено място
     *                        o address - адрес
     */
    public static function getFromBrra($eik)
    {
        // Ако е валиден български ЕИК, прави се опит за извличане от търговския регистър
        if (drdata_Vats::isBulstat($eik)) {
            $registryContent = @file_get_contents("https://portal.registryagency.bg/CR/api/Deeds/{$eik}");
            $result = json_decode($registryContent);
            
            // Ако е намерена фирма, извлича се
            if (!empty($result->fullName)) {
                $data = new stdClass();
                $data->name = $result->fullName;
                $data->country = drdata_Countries::fetchField("#letterCode2 = 'BG'", 'id');
                
                if (is_array($result->sections)) {
                    foreach ($result->sections as $section) {
                        if (is_array($section->subDeeds[0]->groups[0]->fields)) {
                            $foundAddress = array_filter($section->subDeeds[0]->groups[0]->fields, function ($a) {
                                return $a->nameCode == 'CR_F_5_L';
                            });
                            if (countR($foundAddress) == 1) {
                                $foundAddress = array_values($foundAddress);
                                $addressHtml = $foundAddress[0]->htmlData;
                                
                                if (!empty($addressHtml)) {
                                    $addressHtml = str_replace('<br />', ' ', $addressHtml);
                                    $address = strip_tags(str_replace('<br/>', ' ', $addressHtml));
                                    
                                    $shortAddress = $address;
                                    $shortAddress = str_replace('бул./ул.', 'ул.', $shortAddress);
                                    $cutPos1 = mb_strpos($shortAddress, 'Населено място');
                                    if ($cutPos1 !== false) {
                                        $shortAddress = mb_substr($shortAddress, $cutPos1);
                                        $shortAddress = str_replace('Населено място: ', '', $shortAddress);
                                    }
                                    $cutPos2 = mb_strpos($shortAddress, 'Телефон:');
                                    if ($cutPos2 !== false) {
                                        $shortAddress = mb_substr($shortAddress, 0, $cutPos2);
                                    }
                                    
                                    $cutPos3 = mb_strpos($shortAddress, 'Адрес на електронна поща:');
                                    if ($cutPos3 !== false) {
                                        $shortAddress = mb_substr($shortAddress, 0, $cutPos3);
                                    }
                                    
                                    $parsedAddress = drdata_ParseAddressBg::parse($shortAddress);
                                    
                                    $data->pCode = $parsedAddress['п.код'];
                                    $data->address = $parsedAddress['addr'];
                                    $data->place = isset($parsedAddress['гр.']) ? $parsedAddress['гр.'] : $parsedAddress['place'];
                                }
                                
                                break;
                            }
                        }
                    }
                }
                
                return $data;
            }
        }
        
        return false;
    }
    
    
    /**
     * Връща данните от VIES
     *
     * @param string $string - ват номер
     *
     * @return false|stdClass - обект с данни или false, ако не намери нищо
     *                        o name    - име
     *                        o country - ид на държава
     *                        o pCode   - пощенски код
     *                        o place   - населено място
     *                        o address - адрес
     */
    public static function getFromVies($vat)
    {
        // Проверка дали е валиден ват номер
        list($status, , $name, $address) = cls::get('drdata_Vats')->checkStatus($vat);
        if ($status == 'valid') {
            
            // Ако е валиден извлича се името
            $data = new stdClass();
            $data->name = $name;
            $countryCode = substr($vat, 0, 2);
            $data->country = drdata_Countries::fetchField(array("#letterCode2 = '[#1#]'", $countryCode), 'id');
            $address = str::removeWhiteSpace($address, ' ');
            
            // Ако фирмата е от България, прави се опит за парсиране на български адрес
            if ($countryCode == 'BG') {
                $parsedAddress = drdata_ParseAddressBg::parse($address);
                foreach (array('pCode' => 'п.код', 'place' => 'place', 'address' => 'addr') as $fld => $key) {
                    if (!empty($parsedAddress[$key])) {
                        $data->{$fld} = $parsedAddress[$key];
                    }
                }
                
                if (!empty($data->place)) {
                    $data->place = trim(str_replace('гр.', '', $data->place));
                }
            } else {
                $data->address = $address;
            }
            
            return $data;
        }
        
        return false;
    }
}
