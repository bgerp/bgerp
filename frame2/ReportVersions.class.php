<?php


/**
 * Мениджър за предишни състояния на динамичните справки
 *
 *
 * @category  bgerp
 * @package   frame2
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class frame2_ReportVersions extends core_Detail
{
    /**
     * Заглавие на мениджъра
     */
    public $title = 'История на промяната на справките';
    
    
    /**
     * Права за добавяне
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Права за редактиране
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Права за запис
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Права за избор на версия
     */
    public $canCheckout = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'no_one';
    
    
    /**
     * Необходими плъгини
     */
    public $loadList = 'plg_Created';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'reportId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'createdOn=Версия,createdBy=От';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 5;
    
    
    /**
     * Име на перманентните данни
     */
    const PERMANENT_SAVE_NAME = 'reportVersions';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('reportId', 'key(mvc=frame2_Reports)', 'caption=Справка');
        $this->FLD('oldRec', 'blob(serialize, compress,size=20000000)', 'caption=Стар запис');
        $this->FLD('versionBefore', 'int', 'caption=Предишна версия');
        
        $this->setDbIndex('versionBefore');
    }
    
    
    /**
     * Записване на нова версия на отчета
     *
     * @param int      $reportId - ид на справка
     * @param stdClass $rec      - за записване
     */
    public static function log($reportId, $rec)
    {
        // Записа на новата версия
        $logRec = (object) array('reportId' => $reportId, 'oldRec' => $rec, 'versionBefore' => null);
        
        // Опит за намиране на последната записана версия
        $query = self::getQuery();
        $query->where("#reportId = {$reportId}");
        $query->orderBy('createdOn', 'DESC');
        
        // Ако има такава
        if ($lastRec = $query->fetch()) {
            
            // Сравнява се с новата
            $obj1 = self::getDataToCompare($lastRec->oldRec);
            $obj2 = self::getDataToCompare($rec);
            
            // Ако няма промяна на данните, не се записва нова версия
            if (serialize($obj1) == serialize($obj2)) {
                
                return false;
            }
            $logRec->versionBefore = $lastRec->id;
        }
        
        // Запис на новата версия
        $id = self::save($logRec);
        
        // Контрол на версиите
        self::keepInCheck($reportId);
        
        return $id;
    }
    
    
    /**
     * Подготвя данните на справката във подходящ формат за сравнение
     *
     * @param stdClass $rec
     *
     * @return stdClass $obj
     */
    private static function getDataToCompare($rec)
    {
        $obj = new stdClass();
        
        // Изчислената дата
        $obj->data = $rec->data;
        
        // И полетата от драйвера
        if ($Driver = frame2_Reports::getDriver($rec)) {
            $fields = frame2_Reports::getDriverFields($Driver);
            foreach ($fields as $name => $caption) {
                $obj->{$name} = $rec->{$name};
            }
        }
        
        // Връщане на нормализирания обект
        return $obj;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        // Коя е избраната версия в момента
        $selectedId = frame2_Reports::getSelectedVersionId($rec->reportId);
        if (!$selectedId) {
            $latestVersionId = self::getLatestVersionId($rec->reportId);
            $selectedId = $latestVersionId;
        }
        
        // Бутон за избор на текуща версия
        if ($mvc->haveRightFor('checkout', $rec->id)) {
            
            // Правилно рет урл
            $singleUrl = frame2_Reports::getSingleUrlArray($rec->reportId);
            $vId = Request::get('vId', 'int');
            if ($vId == $rec->reportId) {
                $singleUrl['vId'] = $vId;
            }
            
            $url = array($mvc, 'checkout', $rec->id, 'ret_url' => $singleUrl);
            $icon = ($rec->id == $selectedId) ? 'img/16/radio-button.png' : 'img/16/radio-button-uncheck.png ';
            $row->createdOn = ht::createLink($row->createdOn, $url, false, "ef_icon={$icon},title=Избор на версия");
            
            if($rec->id == $selectedId){
                $row->ROW_ATTR['style'] = 'background-color:#fefec2';
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'checkout' && isset($rec->id)) {
            $requiredRoles = frame2_Reports::getRequiredRoles('single', $rec->reportId);
        }
    }
    
    
    /**
     * Преди извличане на записите от БД
     */
    protected static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('id', 'DESC');
    }
    
    
    /**
     * Подготовка на Детайлите
     */
    public function prepareDetail_($data)
    {
        $vId = Request::get('vId', 'int');
        if (empty($vId) || $vId != $data->masterId) {
            $data->render = false;
            
            return;
        }
        
        parent::prepareDetail_($data);
    }
    
    
    /**
     * Рендиране на детайла
     */
    public function renderDetail_($data)
    {
        // Не се рендира детайла, ако има само една версия или режима е само за показване
        if ($data->render === false || count($data->recs) == 1 || Mode::isReadOnly()) {
            
            return new core_ET('');
        }
        
        return parent::renderDetail_($data);
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $data->listTableMvc->setField('createdBy', 'smartCenter');
    }
    
    
    /**
     * Екшън за избор на текуща версия на справката
     */
    public function act_Checkout()
    {
        // Проверки
        $this->requireRightFor('checkout');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('checkout', $rec);
        
        self::checkoutVersion($rec->reportId, $rec->id);
        
        // Редирект към спавката
        return followRetUrl();
    }
    
    
    /**
     * Колко е максималния брой на версиите за пазене
     *
     * @param int $reportId - ид на справка
     *
     * @return int $maxKeepHistory  - максимален брой пазения
     */
    private static function getMaxCount($reportId)
    {
        $maxKeepHistory = frame2_Reports::fetchField($reportId, 'maxKeepHistory');
        if (!isset($maxKeepHistory)) {
            $maxKeepHistory = frame2_Setup::get('MAX_VERSION_HISTORT_COUNT');
        }
        
        return $maxKeepHistory;
    }
    
    
    /**
     * Поддържа допустимия брой на версиите
     *
     * @param int $reportId
     *
     * @return int|NULL
     */
    public static function keepInCheck($reportId)
    {
        // максимален брой на изтриване
        $maxCount = self::getMaxCount($reportId);
        
        // Намиране на всички версии
        $query = self::getQuery();
        $query->where("#reportId = {$reportId}");
        $query->orderBy('id', 'ASC');
        $query->show('id,reportId');
        
        // Ако ограничението е надминато
        $count = $query->count();
        
        // Изтриване на стари версии
        if ($count > $maxCount) {
            while ($rec = $query->fetch()) {
                self::unSelectVersion($rec->reportId);
                self::delete($rec->id);
                $count--;
                
                if ($count <= $maxCount) {
                    break;
                }
            }
        }
    }
    
    
    /**
     * Коя е последната версия на спавката
     *
     * @param int $reportId - ид на справката
     *
     * @return int - ид на последната версия
     */
    public static function getLatestVersionId($reportId)
    {
        $query = self::getQuery();
        $query->where("#reportId = {$reportId}");
        $query->orderBy('id', 'DESC');
        $query->show('id');
        
        return $query->fetch()->id;
    }
    
    
    /**
     * Деселектиране на избраната версия на отчета от сета
     *
     * @param int $reportId
     *
     * @return void
     */
    public static function unSelectVersion($reportId)
    {
        $versionArr = Mode::get(self::PERMANENT_SAVE_NAME);
        if (!isset($versionArr[$reportId])) {
            
            return;
        }
        
        unset($versionArr[$reportId]);
        Mode::setPermanent(self::PERMANENT_SAVE_NAME, $versionArr);
    }
    
    
    /**
     * Избор на определена версия
     *
     * @param int $reportId  - ид на отчет
     * @param int $versionId - ид на версия
     *
     * @return void
     */
    public static function checkoutVersion($reportId, $versionId)
    {
        $versionArr = Mode::get(self::PERMANENT_SAVE_NAME);
        $versionArr = is_array($versionArr) ? $versionArr : array();
        $versionArr[$reportId] = $versionId;
        Mode::setPermanent(self::PERMANENT_SAVE_NAME, $versionArr);
    }
}
