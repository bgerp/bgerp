<?php



/**
 * Клас 'drdata_Vats' -
 *
 *
 * @category  vendors
 * @package   drdata
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class drdata_Vats extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Sorting,drdata_Wrapper,plg_RowTools2';
    
    
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
    static $unknowTTL = 86400;
    
    
    
    /**
     * Заглавие
     */
    var $title = 'Регистър на данъчните номера';
    
    
    /**
     * Кой  може да пише?
     */
    var $canWrite = 'admin';
    
    
    /**
     * 
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin';
    

    /**
     * Списък с VAT номера, които трябва да се обновят на shutdown
     */
    var $updateOnShutdown = array();


    /**
     * Описание на модела (таблицата)
     */
    function description()
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
    function act_Check()
    {
        $form = cls::get('core_Form');
        $form->title = 'Проверка на VAT номер';
        $form->FNC('vat', 'varchar(32)', 'caption=VAT номер,input');
        $form->toolbar->addSbBtn('Провери');
        $form->input();
        
        if ($form->isSubmitted()) {
            if (!(strlen($vat = core_Type::escape(trim($form->input()->vat))))) {
                $res = new Redirect (array($this, 'Check'), '|Не сте въвели VAT номер');
            } else {
                list($status, ) = $this->check($vat);  
                switch($status) {
                    case 'valid' :
                        $res = new Redirect (array($this), "|VAT номера|* <i>'{$vat}'</i> |е валиден|*");
                        break;
                    case 'bulstat' :
                        $res = new Redirect (array($this), "|Номера|* <i>'{$vat}'</i> |е валиден БУЛСТАТ/ЕИК|*");
                        break;
                    case 'syntax' :
                        $res = new Redirect (array($this), "|VAT номера|* <i>'{$vat}'</i> |е синтактично грешен|*");
                        break;
                    case 'invalid' :
                        $res = new Redirect (array($this), "|VAT номера|* <i>'{$vat}'</i> |е невалиден|*");
                        break;
                    case 'unknown' :
                        $res = new Redirect (array($this), "|Не може да се определи статуса на VAT номера|* <i>'{$vat}'</i>");
                        break;
                    case 'not_vat' :
                        $res = new Redirect (array($this), "|Това не е VAT номер|* - <i>'{$vat}'</i>");
                        break;
                    default : expect(FALSE);
                }
            }
            
            return $res;
        }
        
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Генерира бутон, който препраща в страница за проверка на VAT номер
     */
    static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $data->toolbar->addBtn('Проверка на VAT номер', array($mvc, 'Check'));
    }


    /**
     * Подреждане - първо новите
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('lastChecked', 'DESC');
    }
    
    
    /**
     * Пълна проверка на VAT номер - синтактична + онлайн проверка.
     *
     * @param string $vat
     * @return string 'syntax', 'valid', 'invalid', 'unknown'
     */
    public function check($vat)
    {
        $canonocalVat = $this->canonize($vat);
        
        $rec = $this->fetch(array("#vat = '[#1#]'", $canonocalVat));
                
        if(!$rec) {
            // Ако нямаме кеширан запис за този VAT номер, създаваме нов и го записваме
            $rec = new stdClass();
            list($rec->status, $rec->info) = $this->checkStatus($canonocalVat);
            $rec->vat = $canonocalVat;
            $rec->lastUsed = $rec->lastChecked = dt::verbal2mysql();
            if(in_array($rec->status, array('valid', 'invalid', 'unknown'))) {
                $this->save($rec);
            }
        } else {
            // Проверяваме дали кеша не е изтекъл
            $expDate = dt::subtractSecs(drdata_Setup::get('VAT_TTL'));
            $lastUsedExp = dt::subtractSecs(drdata_Setup::get('LAST_USED_EXP'));
            $expUnknown = dt::subtractSecs(self::$unknowTTL);
            
            $rec->lastUsed = dt::verbal2mysql();
            $this->save($rec, 'lastUsed');
            
            // Ако информацията за данъчния номер е остаряла или той е неизвестен и не сме го проверявали последните 24 часа 
            if((($rec->lastChecked <= $expDate) && ($rec->lastUsed >= $lastUsedExp)) || ($rec->status == self::statusUnknown && $rec->lastChecked < $expUnknown) ) {
                
                // Ако не е достигнат максимума, добавяме и този запис за обновяване
                if(count($this->updateOnShutdown) < self::MAX_CNT_VATS_FOR_UPDATE) {
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
     * @return string 'valid', 'invalid', 'unknown'
     */
    function checkStatus($vat)
    {   
        // Ако номера не е VAT, тогава само проверяваме, дали не е валиден BULSTAT
        if(!$this->isHaveVatPrefix($vat)) {
            if(self::isBulstat($vat)) {
                $res = self::statusBulstat;
            } else {
                $res = self::statusNotVat;
            }
        }
        
        // Ако синтаксиса не отговаря на VAT, статуса сигнализира за това
        if(!$res && !$this->checkSyntax($vat)) {
            $res = self::statusSyntax;
        }
        
        if(!$res) {
            // Конвериране на български 13-цифрени данъчни номера към 9-цифрени
            if((strpos($vat, 'BG')) === 0 && (strlen($vat) == 15)) {
                $vat = substr($vat, 0, 11);
            }
            
            $countryCode = substr($vat, 0, 2);
            $vatNumber = substr($vat, 2);
            
            try {
                
//                 ini_set("default_socket_timeout", 5);
                
                $client = @new SoapClient("http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl", array("connection_timeout" => 4));
                
                $params = array('countryCode' => $countryCode, 'vatNumber' => $vatNumber);
                @$result = $client->checkVat($params);
            } catch (Exception $e) {
                reportException($e);
                $result = new stdClass();
            } catch (Throwable $t) {
                reportException($t);
                $result = new stdClass();
            }
            
            $res = self::statusUnknown;
            
            if (is_object($result)) {
                if ($result->valid === TRUE) {
                    $res = self::statusValid;
                    $info = $result->name . "\n" . $result->address;
                } elseif ($result->valid === FALSE) {
                    $res = self::statusInvalid;
                }
            }
        }
        
        return array($res, $info);
    }

    
    /**
     * Обновяване на статуса на VAT номера след залез
     */
    function on_Shutdown()
    {
        foreach ($this->updateOnShutdown as $rec) {
            list($rec->status, $rec->info) = $this->checkStatus($rec->vat);
            $rec->lastChecked = dt::verbal2mysql();
            $this->save($rec, 'status, info, lastChecked');
        }
    }
    

    /**
     * Проверява дали номерът започва с префикс, като за VAT
     */
    public static function isHaveVatPrefix($value)
    {
        $vatPrefixes = arr::make("BE,BG,CY,CZ,DK,EE,EL,DE,PT,FR,FI,HR,HU,LU,MT,SI,IE,IT,LV,LT,NL,PL,SK,RO,SE,ES,GB,AT", TRUE);
        
        if($vatPrefixes[substr(strtoupper(trim($value)), 0, 2)]) {
 
            return TRUE;
        } else {
            
            return FALSE;
        }
    }
    
    
    /**
     * Синтактична валидация на VAT номер от Европейския съюз
     *
     * @see http://php.net/manual/de/function.preg-match.php
     *
     * @param integer $vat VAT number to test e.g. GB123 4567 89
     * @return integer -1 if country not included OR 1 if the VAT Num matches for the country OR 0 if no match
     */
    function checkSyntax($vat)
    {
        switch(strtoupper(substr($vat, 0, 2))) {
            case 'AT' :
                $regex = '/^ATU[0-9]{8}$/i';
                break;
            case 'BE' :
                $regex = '/^BE[0]{0,1}[0-9]{9}$/i';
                break;
            case 'BG' :
                $regex = '/^BG[0-9]{9,10}$/i';
                break;
            case 'CY' :
                $regex = '/^CY[0-9]{8}[A-Z]$/i';
                break;
            case 'CZ' :
                $regex = '/^CZ[0-9]{8,10}$/i';
                break;
            case 'DK' :
                $regex = '/^DK([0-9]{2}[\ ]{0,1}){3}[0-9]{2}$/i';
                break;
            case 'EE' :
            case 'DE' :
            case 'PT' :
            case 'EL' :
                $regex = '/^(EE|EL|DE|PT)[0-9]{9}$/i';
                break;
            case 'FR' :
                $regex = '/^FR[0-9A-Z]{2}[\ ]{0,1}[0-9]{9}$/i';
                break;
            case 'FI' :
            case 'HU' :
            case 'LU' :
            case 'MT' :
            case 'SI' :
                $regex = '/^(FI|HU|LU|MT|SI)[0-9]{8}$/i';
                break;
            case 'HR' : 
                $regex = '/^HR(\d{11})$/';
                break; 
            case 'IE' :
                $regex = '/^IE[0-9][0-9A-Z\+\*][0-9]{5}[A-Z]$/i';
                break;
            case 'IT' :
            case 'LV' :
                $regex = '/^(IT|LV)[0-9]{11}$/i';
                break;
            case 'LT' :
                $regex = '/^LT([0-9]{9}|[0-9]{12})$/i';
                break;
            case 'NL' :
                $regex = '/^NL[0-9]{9}B[0-9]{2}$/i';
                break;
            case 'PL' :
            case 'SK' :
                $regex = '/^(PL|SK)[0-9]{10}$/i';
                break;
            case 'RO' :
                $regex = '/^RO[0-9]{2,10}$/i';
                break;
            case 'SE' :
                $regex = '/^SE[0-9]{12}$/i';
                break;
            case 'ES' :
                $regex = '/^ES([0-9A-Z][0-9]{7}[A-Z])|([A-Z][0-9]{7}[0-9A-Z])$/i';
                break;
            case 'GB' :
                $regex = '/^GB([1-9][0-9]{2}[\ ]{0,1}[0-9]{4}[\ ]{0,1}[0-9]{2})|([1-9][0-9]{2}[\ ]{0,1}[0-9]{4}[\ ]{0,1}[0-9]{2}[\ ]{0,1}[0-9]{3})|((GD|HA)[0-9]{3})$/i';
                break;
            default : 
                return FALSE;
        }


        return preg_match($regex, $vat);
    }
    
    
    /**
     * Връща каноническото представяне на VAT номер - големи букви, без интервали.
     *
     * @param string $vat
     */
    function canonize($vat)
    {
        $canonicalVat = preg_replace('/[^a-z\d]/i', '', $vat);
        $canonicalVat = strtoupper($canonicalVat);
        
        return $canonicalVat;
    }
    
    
    /**
     * Проверява дали това е валиден български булстат
     */
    static function isBulstat($inBULSTAT)
    {
        for ($i = 0 ; $i <= strlen($inBULSTAT); $i++) {
            $c = substr($inBULSTAT, $i, 1);
            
            if ($c >= "0" && $c <= "9") {
                $BULSTAT .= $c;
            }
        }
        
        switch (strlen($BULSTAT)) {
            case 9 :
                for ($i = 0; $i < 8; $i++) {
                    $c = $c + ((int) substr($BULSTAT, $i, 1)) * ($i + 1);
                }
                $c = $c % 11;
                
                if ($c == 10) {
                    $c = 0;
                    
                    for ($i = 0; $i < 8; $i++) {
                        $c = $c + ((int) substr($BULSTAT, $i, 1)) * ($i + 3);
                    }
                    $c = ($c % 11) % 10;
                }
                
                return (int)substr($BULSTAT, 8, 1) == $c;
            
            case 13 :
                $v1 = array (2, 7, 3, 5);
                $v2 = array (4, 9, 5, 7);
                
                for ($i = 8; $i < 12; $i++) {
                    $c = $c + ((int) substr($BULSTAT, $i, 1)) * $v1[$i-8] ;
                }
                $c = $c % 11;
                
                if ($c == 10) {
                    $c = 0;
                    
                    for ($i = 8; $i < 12; $i++) {
                        $c = $c + ((int) substr($BULSTAT, $i, 1)) * $v2[$i-8];
                    }
                    $c = ($c % 11) % 10;
                }
                
                return ((int) substr($BULSTAT, 12, 1) == $c) && drdata_Vats::isBULSTAT(substr($BULSTAT, 0, 9)) ;
        }
        
        return FALSE;
    }
    
    
    /**
     * Функция връщаща ЕИК то по зададен VAT номер, Ако е подаден ЕИК се връща
     * директно
     * @param string $vatNo - Ват номер
     * @return string - ЕИК номера извлечен от Ват-а, или ако е ЕИК
     * директно го връща
     */
    public static function getUicByVatNo($vat)
    {
    	$self = cls::get(get_called_class());
    	
        $vat = $self->canonize($vat);
    	
        if(substr($vat, 0, 2) == 'BG') {
            $uic = substr($vat, 2);
        }
    	
    	return $uic;
    }
    
    
    /**
     * Извиква се от крона. Премахва старите статус съобщения
     */
    function cron_checkVats()
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
    static function on_AfterSetupMVC($mvc, &$res)
    {
        // Данни за работата на cron
        $rec = new stdClass();
        $rec->systemId = 'checkVats';
        $rec->description = 'Проверка на VAT номера';
        $rec->controller = $mvc->className;
        $rec->action = 'checkVats';
        $rec->period = 10;
        $rec->offset = rand(0,8);
        $rec->delay = 0;
        $rec->timeLimit = 200;
        $res .= core_Cron::addOnce($rec);
    }
}