<?php


/**
 * Клас 'drdata_Eori' - Проверка и валидиране на EORI номера
 *
 *
 * @category  bgerp
 * @package   drdata
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
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

        $rec = $this->fetch(array("#eori = '[#1#]'", $canonocalEori));

        if (!$rec) {
            // Ако нямаме кеширан запис за този EORI номер, създаваме нов и го записваме
            $rec = new stdClass();
            list($rec->status, $rec->info) = $this->checkStatus($canonocalEori);
            $rec->eori = $canonocalEori;
            $rec->lastUsed = $rec->lastChecked = dt::verbal2mysql();
            if (in_array($rec->status, array(self::statusValid, self::statusInvalid, self::statusUnknown))) {
                $this->save($rec, NULL, 'IGNORE');
            }
        } else {
            // Проверяваме дали кеша не е изтекъл
            $expDate = dt::subtractSecs(drdata_Setup::get('EORI_TTL'));
            $lastUsedExp = dt::subtractSecs(drdata_Setup::get('LAST_USED_EXP'));
            $expUnknown = dt::subtractSecs(self::$unknowTTL);
            
            $rec->lastUsed = dt::verbal2mysql();
            $this->save($rec, 'lastUsed');
            
            // Ако информацията за данъчния номер е остаряла или той е неизвестен и не сме го проверявали последните 24 часа
            if ($force || ((($rec->lastChecked <= $expDate) && ($rec->lastUsed >= $lastUsedExp)) || ($rec->status == self::statusUnknown && $rec->lastChecked < $expUnknown))) {
                
                // Ако не е достигнат максимума, добавяме и този запис за обновяване
                if (countR($this->updateOnShutdown) < self::MAX_CNT_EORI_FOR_UPDATE) {
                    $this->updateOnShutdown[] = $rec;
                }
            }
        }
        
        return array($rec->status, $rec->info);
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
        $info = null;
        $rArr = array();

        // Ако синтаксиса не отговаря на EORI, статуса сигнализира за това
        if (!$this->checkSyntax($eori)) {
            $res = self::statusSyntax;
        }

        $curl = curl_init('https://api.service.hmrc.gov.uk/customs/eori/lookup/check-multiple-eori');

        $params = new stdClass();
        $params->eoris = array();
        $params->eoris[] = $eori;

        // Да не се проверява сертификата
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Accept: application/json"));

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');

        curl_setopt($curl, CURLOPT_POSTFIELDS, @json_encode($params));

        core_Debug::startTimer('drdata_Eori_check');
        $responseJson = @curl_exec($curl);
        core_Debug::stopTimer('drdata_Eori_check');

        if (!$res) {
            if (!$responseJson) {
                $res = self::statusInvalid;
            } else {
                $response = @json_decode($responseJson);

                if (!$response) {
                    $res = self::statusInvalid;
                } else {
                    if ($response[0]->valid) {
                        $res = self::statusValid;
                        if ($response[0]->companyDetails) {
                            $info = $response[0]->companyDetails->address->postcode . ', ' . $response[0]->companyDetails->address->cityName . "\n" .
                                $response[0]->companyDetails->address->streetAndNumber . "\n" .
                                $response[0]->companyDetails->traderName;

                            $rArr['name'] = $response[0]->companyDetails->traderName;
                            $rArr['street'] = $response[0]->companyDetails->address->streetAndNumber;
                            $rArr['postalCode'] = $response[0]->companyDetails->address->postcode;
                            $rArr['city'] = $response[0]->companyDetails->address->cityName;
                        }
                    } else {
                        $res = self::statusInvalid;
                    }
                }
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
        
        if ($data->listFilter->rec->eoriNum) {
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
