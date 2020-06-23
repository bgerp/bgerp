<?php


/**
 * Базов драйвер за новите справки
 *
 *
 * @category  bgerp
 * @package   frame2
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class frame2_driver_Proto extends core_BaseClass
{
    /**
     * Интерфейси които имплементира
     */
    public $interfaces = 'frame2_ReportIntf';
    
    
    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = '*';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
    }
    
    
    /**
     * Кой може да избере драйвера
     */
    public function canSelectDriver($userId = null)
    {
        return core_Users::haveRole($this->canSelectDriver, $userId);
    }
    
    
    /**
     * Връща заглавието на отчета
     *
     * @param stdClass $rec - запис
     *
     * @return string|NULL - заглавието или NULL, ако няма
     */
    public function getTitle($rec)
    {
    }
    
    
    /**
     * Подготвя данните на справката от нулата, които се записват в модела
     *
     * @param stdClass $rec - запис на справката
     *
     * @return stdClass|NULL $data - подготвените данни
     */
    public function prepareData($rec)
    {
    }
    
    
    /**
     * Рендиране на данните на справката
     *
     * @param stdClass $rec - запис на справката
     *
     * @return core_ET - рендирания шаблон
     */
    public function renderData($rec)
    {
        return new core_ET('');
    }
    
    
    /**
     * Да се изпраща ли нова нотификация на споделените потребители, при опресняване на отчета
     *
     * @param stdClass $rec
     *
     * @return bool
     */
    public function canSendNotificationOnRefresh($rec)
    {
        return true;
    }
    
    
    /**
     * Връща параметрите, които ще бъдат заместени в текста на нотификацията
     *
     * @param stdClass $rec
     *
     * @return array
     */
    public function getNotificationParams($rec)
    {
        $params = array();
        $params['handle'] = '#' . frame2_Reports::getHandle($rec->id);
        $params['lastRefreshed'] = frame2_Reports::getVerbal($rec, 'lastRefreshed');
        
        return $params;
    }
    
    
    /**
     * Добавя допълнителни полетата в антетката
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param NULL|array          $resArr
     * @param object              $rec
     * @param object              $row
     */
    public static function on_AfterGetFieldForLetterHead(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$resArr, $rec, $row)
    {
        $form = cls::get('core_Form');
        $Driver->addFields($form);
        $fields = (is_array($form->fields)) ? $form->fields : array();
        
        foreach ($fields as $name => $fld) {
            if (isset($rec->{$name}) && $fld->single !== 'none') {
                $captionArr = explode('->', $fld->caption);
                $caption = (countR($captionArr) == 1) ? $captionArr[0] : $captionArr[1];
                $resArr[$name] = array('name' => tr($caption), 'val' => $row->{$name});
            }
        }
    }
    
    
    /**
     * Връща редовете на CSV файл-а
     *
     * @param stdClass $rec
     *
     * @return array
     */
    public function getCsvExportRows($rec)
    {
        return array();
    }
    
    
    /**
     * Връща полетата за експортиране във csv
     *
     * @param stdClass $rec
     *
     * @return array
     */
    public function getCsvExportFieldset($rec)
    {
        return new core_FieldSet();
    }
    
    
    /**
     * Връща следващите три дати, когато да се актуализира справката
     *
     * @param stdClass $rec - запис
     *
     * @return array|FALSE - масив с три дати или FALSE ако не може да се обновява
     */
    public function getNextRefreshDates($rec)
    {
        return array();
    }
    
    
    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     *
     * @param stdClass $rec
     *
     * @return array
     */
    public function getChangeableFields($rec)
    {
        $changeableFields = $this->changeableFields;
        
        // Ако е зададено '*', значи са всички полета от драйвера
        if ($changeableFields === '*') {
            $fs = new core_FieldSet();
            $this->addFields($fs);
            $changeableFields = array_keys($fs->selectFields());
        }
        
        return arr::make($changeableFields, true);
    }
    
    
    /**
     * Какъв ще е езика с който ще се рендират данните на шаблона
     *
     * @param stdClass $rec
     *
     * @return string|null езика с който да се рендират данните
     */
    public function getRenderLang($rec)
    {
        return null;
    }
    
    
    /**
     * Връща шаблоните за етикети към драйвера
     *
     * @param mixed $id
     *
     * @return array - достъпни шаблони
     */
    public function getLabelTemplates($id)
    {
        return label_Templates::getTemplatesByClass($this);
    }
    
    
    /**
     * Може ли справката да бъде изпращана по имейл
     *
     * @param mixed $rec
     *
     * @return boolean
     */
    public function canBeSendAsEmail($rec)
    {
        return false;
    }
    
    
    /**
     * Изборът на потребители които да бъдат нотифицирани при обновяване дали са задължителни
     *
     * @param mixed $rec
     *
     * @return boolean
     */
    public function requireUserForNotification($rec)
    {
        return true;
    }
    
    
    /**
     * Иконка на справката
     *
     * @param mixed $id
     *
     * @return string
     */
    public static function getIcon($id)
    {
        return 'img/16/report.png';
    }
}
