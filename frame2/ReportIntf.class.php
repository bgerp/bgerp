<?php


/**
 * Интерфейс за създаване на справки във системата
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
class frame2_ReportIntf extends embed_DriverIntf
{
    /**
     * Инстанция на класа имплементиращ интерфейса
     */
    public $class;
    
    
    /**
     * Кои полета, може да се променят от poweruser
     */
    protected $changeableFields;
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        return $this->class->addFields($fieldset);
    }
    
    
    /**
     * Кой може да избере драйвера
     */
    public function canSelectDriver($userId = null)
    {
        return $this->class->canSelectDriver($userId);
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
        return $this->class->getTitle($rec);
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
        return $this->class->prepareData($rec);
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
        return $this->class->renderData($rec);
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
        return $this->class->canSendNotificationOnRefresh($rec);
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
        return $this->class->getNotificationParams($rec);
    }
    
    
    /**
     * Връща редовете на CSV файл-а
     *
     * @param stdClass       $rec         - запис
     * @param core_BaseClass $ExportClass - клас за експорт
     *
     * @return array $recs                - записите за експорт
     */
    public function getExportRecs($rec, $ExportClass)
    {
        return $this->class->getExportRecs($rec, $ExportClass);
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
        return $this->class->getCsvExportFieldset($rec);
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
        return $this->class->getNextRefreshDates($rec);
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
        return $this->class->getChangeableFields($rec);
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
        return $this->class->getRenderLang($rec);
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
        return $this->class->getLabelTemplates($id);
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
        return $this->class->canBeSendAsEmail($rec);
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
        return $this->class->requireUserForNotification($rec);
    }
}
