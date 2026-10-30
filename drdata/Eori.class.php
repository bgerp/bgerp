<?php


/**
 * Клас 'drdata_Eori' - Проверка и валидиране на EORI номера
 *
 *
 * @category  bgerp
 * @package   drdata
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class drdata_Eori extends core_Manager
{
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Sorting, drdata_Wrapper, plg_RowTools2';

    
    /**
     * Това е EORI номер, но с невалиден синтаксис
     * color:red
     */
    const statusSyntax = 'syntax';
    
    
    /**
     * Това е EORI номер с правилен синтаксис, но не е известно дали е валиден
     * color:green
     */
    const statusUnknown = 'unknown';
    
    
    /**
     * Това е EORI номер с правилен синтаксис, но не е валиден
     * color:red
     */
    const statusInvalid = 'invalid';
    
    
    /**
     * Това е валиден EORI номер
     * color:black
     */
    const statusValid = 'valid';
    
    
    /**
     * Колко най-много eori номера да бъдат обновени след залез?
     */
    const MAX_CNT_EORI_FOR_UPDATE = 1;
    
    
    /**
     * Колко най-много eori номера (по cron) да бъдат обновени след залез?
     */
    const CRON_MAX_CNT_EORI_FOR_UPDATE = 5;
    
    
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
     * Списък с EORI номера, които трябва да се обновят на shutdown
     */
    public $updateOnShutdown = array();


    /**
     * Резултати от проверките, извършени в текущата заявка
     */
    protected $checkedEori = array();
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $unknown = self::statusUnknown;
        $statusInvalid = self::statusInvalid;
        $statusValid = self::statusValid;
        $statusSyntax = self::statusSyntax;

        $this->FLD('eori', 'drdata_type_Eori', 'caption=EORI');
        $this->FLD('status', "enum({$unknown}=Непознат, {$statusInvalid}=Некоректен, {$statusValid}=Валиден, {$statusSyntax}=Грешен синтаксис)", 'caption=Състояние,input=none');
        $this->FLD('lastChecked', 'datetime(format=smartTime)', 'caption=Проверен на,input=none');
        $this->FLD('lastUsed', 'datetime(format=smartTime)', 'caption=Използван на,input=none');
        $this->FLD('info', 'text', 'caption=Информация');
        
        $this->setDbUnique('eori');
    }
    
    
    /**
     * Проверява за съществуващ EORI номер
     */
    public function act_Check()
    {
        requireRole('admin');

        $form = cls::get('core_Form');
        $form->title = 'Проверка на EORI номер';
        $form->FNC('eori', 'varchar(32)', 'caption=EORI номер,input,mandatory');
        $form->toolbar->addSbBtn('Провери');
        $form->input();

        if ($form->isSubmitted()) {
            $eori =$this->canonize($form->rec->eori);

            if (!strlen($eori)) {
                $res = new Redirect(array($this, 'Check'), '|Не сте въвели EORI номер');
            } else {
                list($status, ) = $this->check($eori, true);
                switch ($status) {
                    case self::statusValid:
                        $res = new Redirect(array($this), "|EORI номера|* <i>'{$eori}'</i> |е валиден|*");
                        break;
                    case self::statusSyntax:
                        $res = new Redirect(array($this), "|EORI номера|* <i>'{$eori}'</i> |е синтактично грешен|*");
                        break;
                    case self::statusInvalid:
                        $res = new Redirect(array($this), "|EORI номера|* <i>'{$eori}'</i> |е невалиден|*");
                        break;
                    case self::statusUnknown:
                        $res = new Redirect(array($this), "|Не може да се определи статуса на EORI номера|* <i>'{$eori}'</i>");
                        break;
                    default: expect(false);
                }
            }
            
            return $res;
        }
        
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Генерира бутон, който препраща в страница за проверка на EORI номер
     */
    public static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $data->toolbar->addBtn('Проверка на EORI номер', array($mvc, 'Check'));
    }
    
    
    /**
     * Подреждане - първо новите
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('lastChecked', 'DESC');
    }
    
    
    /**
     * Пълна проверка на EORI номер - синтактична + онлайн проверка.
     *
     * @param string $eori
     * @param bool   $force
     *
     * @return string 'syntax', 'valid', 'invalid', 'unknown'
     */
    public function check($eori, $force = false)
    {
        $canonocalEori = $this->canonize($eori);

        if (isset($this->checkedEori[$canonocalEori])) {
            return $this->checkedEori[$canonocalEori];
        }

        $rec = $this->fetch(array("#eori = '[#1#]'", $canonocalEori));

        if (!$rec || $force) {
            // Принудителната проверка трябва да върне актуалния резултат още в тази заявка.
            $rec = $rec ?: new stdClass();
            list($rec->status, $rec->info) = $this->checkStatus($canonocalEori);
            $this->checkedEori[$canonocalEori] = array($rec->status ?? self::statusUnknown, $rec->info ?? null);
            $rec->eori = $canonocalEori;
            $rec->lastUsed = $rec->lastChecked = dt::verbal2mysql();
            if (!empty($rec->id) || in_array($rec->status ?? null, array(self::statusValid, self::statusInvalid, self::statusUnknown))) {
                $this->save($rec, null, empty($rec->id) ? 'IGNORE' : null);
            }
            foreach ($this->updateOnShutdown as $key => $queuedRec) {
                if (($queuedRec->eori ?? null) == $canonocalEori) {
                    unset($this->updateOnShutdown[$key]);
                }
            }
        } else {
            // Проверяваме дали кеша не е изтекъл
            $expDate = dt::subtractSecs(drdata_Setup::get('EORI_TTL'));
            $lastUsedExp = dt::subtractSecs(drdata_Setup::get('LAST_USED_EXP'));
            $expUnknown = dt::subtractSecs(self::$unknowTTL);
            
            $rec->lastUsed = dt::verbal2mysql();
            $this->save($rec, 'lastUsed');
            
            // Ако информацията за данъчния номер е остаряла или той е неизвестен и не сме го проверявали последните 24 часа
            if (((($rec->lastChecked ?? '') <= $expDate) && (($rec->lastUsed ?? '') >= $lastUsedExp)) || (($rec->status ?? null) == self::statusUnknown && ($rec->lastChecked ?? '') < $expUnknown)) {
                
                // Ако не е достигнат максимума, добавяме и този запис за обновяване
                if (countR($this->updateOnShutdown) < self::MAX_CNT_EORI_FOR_UPDATE) {
                    $this->updateOnShutdown[] = $rec;
                }
            }
        }
        
        return array($rec->status ?? self::statusUnknown, $rec->info ?? null);
    }


    /**
     * Проверка за валидността на EORI номер, включително и чрез сървиз на EC
     *
     * @param string $eori Каноничен ват
     *
     * @return array
     * [0] => res
     * [1] => info
     * [3] => infoArr
     */
    public function checkStatus($eori)
    {
        $res = self::statusUnknown;
        $info = null;
        $rArr = array();

        if (!$this->checkSyntax($eori)) {
            return array(self::statusSyntax, $info, $rArr);
        }

        if (strtoupper(substr($eori, 0, 2)) == 'GB') {
            $curl = curl_init('https://api.service.hmrc.gov.uk/customs/eori/lookup/check-multiple-eori');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 4);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Accept: application/json"));
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(array('eoris' => array($eori))));

            core_Debug::startTimer('drdata_Eori_check');
            try {
                $responseJson = @curl_exec($curl);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            } finally {
                core_Debug::stopTimer('drdata_Eori_check');
                curl_close($curl);
            }

            $response = json_decode($responseJson ?: '');
            $result = is_array($response) ? ($response[0] ?? null) : null;
            if ($httpCode == 200 && is_object($result) && ($result->eori ?? null) === $eori) {
                if (($result->valid ?? null) === true) {
                    $res = self::statusValid;
                    $details = $result->companyDetails ?? null;
                    if (is_object($details)) {
                        $address = $details->address ?? null;
                        $rArr['name'] = $details->traderName ?? null;
                        $rArr['street'] = is_object($address) ? ($address->streetAndNumber ?? null) : null;
                        $rArr['postalCode'] = is_object($address) ? ($address->postcode ?? null) : null;
                        $rArr['city'] = is_object($address) ? ($address->cityName ?? null) : null;
                        $info = $rArr['postalCode'] . ', ' . $rArr['city'] . "\n" . $rArr['street'] . "\n" . $rArr['name'];
                    }
                } elseif (($result->valid ?? null) === false) {
                    $res = self::statusInvalid;
                }
            }
        } elseif (class_exists('SoapClient')) {
            // SOAP използва default_socket_timeout и за четенето на отговора.
            $socketTimeout = ini_get('default_socket_timeout');
            ini_set('default_socket_timeout', '10');
            core_Debug::startTimer('drdata_Eori_check');
            try {
                $client = @new SoapClient(
                    'https://ec.europa.eu/taxation_customs/dds2/eos/validation/services/validation?wsdl',
                    array('connection_timeout' => 4)
                );
                $response = @$client->__soapCall('validateEORI', array(array('eori' => $eori)));
                $return = is_object($response) ? ($response->return ?? null) : null;
                $result = is_object($return) ? ($return->result ?? null) : null;
                if (is_object($result) && empty($return->errorDescription) && empty($result->errorReason) && ($result->eori ?? null) === $eori) {
                    if (($result->status ?? null) === 0) {
                        $res = self::statusValid;
                    } elseif (($result->status ?? null) === 1) {
                        $res = self::statusInvalid;
                    }
                }
            } catch (core_exception_Redirect $e) {
                throw $e;
            } catch (Exception $e) {
                $res = self::statusUnknown;
            } finally {
                ini_set('default_socket_timeout', $socketTimeout);
                core_Debug::stopTimer('drdata_Eori_check');
            }
        }

        return array($res, $info, $rArr);
    }
    
    
    /**
     * Обновяване на статуса на EORI номера след залез
     */
    public static function on_Shutdown($mvc)
    {
        foreach ($mvc->updateOnShutdown as $rec) {
            list($rec->status, $rec->info) = $mvc->checkStatus($rec->eori);
            $rec->lastChecked = dt::verbal2mysql();
            $mvc->save($rec, 'status, info, lastChecked');
        }
    }
    
    
    /**
     * Синтактична валидация на EORI номер от Европейския съюз
     *
     * @param string $eori
     */
    public function checkSyntax(&$eori)
    {
        $eori = trim($eori);

        return preg_match('/^[A-Z]{2}[A-Z0-9]{1,15}$/i', $eori);
    }
    
    
    /**
     * Връща каноническото представяне на EORI номер - големи букви, без интервали.
     *
     * @param string $eori
     */
    public function canonize($eori)
    {
        $eori = trim($eori);
        $eori = preg_replace('/[^a-z\d]/i', '', $eori);
        $eori = strtoupper($eori);
        
        return $eori;
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
        $data->listFilter->FNC('eoriNum', 'varchar', 'caption=EORI номер, input');
        $data->listFilter->showFields = 'eoriNum';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->input('eoriNum');
        
        if (!empty($data->listFilter->rec->eoriNum)) {
            $data->query->like('eori', $data->listFilter->rec->eoriNum);
        }
        
        // Сортиране на записите по num
        $data->query->orderBy('lastChecked', 'DESC');
        $data->query->orderBy('lastUsed', 'DESC');
        $data->query->orderBy('eori');
    }
    
    
    /**
     * Извиква се от крона. Премахва старите статус съобщения
     */
    public function cron_checkEori()
    {
        // За да се стартира on_ShutDown
        cls::get(get_called_class());
        
        $expDate = dt::subtractSecs(drdata_Setup::get('EORI_TTL'));
        $lastUsedExp = dt::subtractSecs(drdata_Setup::get('LAST_USED_EXP'));
        $unknownExpDate = dt::subtractSecs(self::$unknowTTL);
        
        $statusUnknown = self::statusUnknown;
        
        $query = $this->getQuery();
        $query->where("#lastChecked <= '{$expDate}'");
        $query->where("#lastUsed >= '{$lastUsedExp}'");
        $query->orWhere("#status = '{$statusUnknown}' AND #lastChecked <= '{$unknownExpDate}'");
        
        $query->limit(self::CRON_MAX_CNT_EORI_FOR_UPDATE);
        
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
        $rec->systemId = 'checkEori';
        $rec->description = 'Проверка на EORI номера';
        $rec->controller = $mvc->className;
        $rec->action = 'checkEori';
        $rec->period = 10;
        $rec->offset = rand(0, 8);
        $rec->isRandOffset = true;
        $rec->delay = 0;
        $rec->timeLimit = 200;
        $res .= core_Cron::addOnce($rec);
    }
}
