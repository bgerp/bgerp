<?php


/**
 * Експортиране на детайлите на документив към клипборда
 *
 * @category  bgerp
 * @package   export
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class export_Clipboard extends core_Mvc
{


    /**
     * Импортиране на csv-файл в даден мениджър
     *
     * @param int $clsId
     * @param int $objId
     *
     * @return bool
     */
    public function canUseExport($clsId, $objId)
    {

        return cls::get('export_Csv')->canUseExport($clsId, $objId);
    }


    /**
     * Заглавие на таблицата
     */
    public $title = 'Експортиране на документ към клипборда';
    
    
    public $interfaces = 'export_ExportTypeIntf';

    
    /**
     * Импортиране на csv-файл в даден мениджър
     *
     * @param int $clsId
     * @param int $objId
     *
     * @return string
     */
    public function getExportTitle($clsId, $objId)
    {

        return 'Към клипборда';
    }


    /**
     * Импортиране на csv-файл в даден мениджър
     *
     * @param core_Form    $form
     * @param int          $clsId
     * @param int|stdClass $objId
     *
     * @return NULL|string
     */
    public function makeExport($form, $clsId, $objId)
    {
        $clsInst = cls::get($clsId);
        $cRec = $clsInst->fetchRec($objId);

        $action = array(
                'action' => doclog_Documents::ACTION_EXPORT,
                'containerId' => $cRec->containerId,
                'threadId' => $cRec->threadId,
        );

        doclog_Documents::pushAction($action);
        $mid = doclog_Documents::saveAction($action);
        doclog_Documents::popAction();

        $lg = '';
        $isPushed = false;
        if ($cRec->template) {
            $lg = $clsInst->pushTemplateLg($cRec->template);
        }

        $userId = core_Users::getCurrent();

        if ($userId < 1) {
            $userId = $cRec->activatedBy;
        }

        if ($userId < 1) {
            $userId = $cRec->createdBy;
        }

        if (($userId < 1) && ($cRec->containerId)) {
            $sContainerRec = doc_Containers::fetch($cRec->containerId);
            $userId = $sContainerRec->activatedBy;
            if ($userId < 1) {
                if ($sContainerRec->modifiedBy >= 0) {
                    $userId = $sContainerRec->modifiedBy;
                } elseif ($sContainerRec->createdBy >= 0) {
                    $userId = $sContainerRec->createdBy;
                }
            }
        }

        if (!$lg) {
            $lg = doc_Containers::getLanguage($cRec->containerId);

            if ($lg && !core_Lg::isGoodLg($lg)) {
                $lg = 'en';
            }

            if ($lg) {
                core_Lg::push($lg);
            }
        }

        $recs = array();

        try {
            $clsArr = core_Classes::getOptionsByInterface('export_DetailExportCsvIntf');

            foreach ($clsArr as $clsName) {
                $inst = cls::getInterface('export_DetailExportCsvIntf', $clsName);
                $csvFields = new core_FieldSet();
                $recs = $inst->getRecsForExportInDetails($clsInst, $cRec, $csvFields, $userId);

                if (!empty($recs)) {
                    break;
                }
            }
        } catch (core_exception_Expect $e) {
        }

        if (!empty($recs)) {

            foreach ($recs as &$rec) {
                $rec = (array) $rec;
            }

            $this->updateKeyVal($recs, $csvFields, $userId, $clsInst->getClassId(), $objId);
        } else {

            return new Redirect(array($clsInst, 'single', $objId), 'Няма данни за експорт', 'warning');
        }

        if ($lg) {
            core_Lg::pop();
        }

        $clsInst->logWrite('Копиране в клипборда', $objId);

        return new Redirect(array($clsInst, 'single', $objId), 'Копирани детайли в клипборда|*: ' . countR($recs));
    }


    /**
     * Функция, която връща стойността кешираните данни
     *
     * @param null|integer $userId
     * @param null|integer $classId
     *
     * @return false|array
     */
    public static function getVals($userId = null, $classId = null)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }


        $pKey = self::getKeyName($userId);

        $vals = core_Permanent::get($pKey);

        if (!$vals) {

            return false;
        }

        if (isset($classId)) {

            if (isset($vals[$classId])) {

                return $vals[$classId];
            }

            return false;
        }

        return $vals;
    }


    /**
     * Обновява кеша със записите
     *
     * @param array $recs
     * @param array $fields
     * @param integer $userId
     * @param integer $classId
     * @param integer $objId
     */
    protected function updateKeyVal($recs, $fields, $userId, $classId, $objId)
    {
        $pKey = $this->getKeyName($userId);

        $pValArr = core_Permanent::get($pKey);

        if (!$pValArr) {
            $pValArr = array();
        }

        unset($pValArr[$classId][$objId]);

        $data = new stdClass();
        $data->recs = $recs;
        $data->fields = $fields;


        $oArr = array($objId => $data);

        if ($pValArr[$classId]) {
            $oArr += $pValArr[$classId];
        }

        $pValArr = array($classId => $oArr) + $pValArr;

        core_Permanent::set($pKey, $pValArr, 1440);
    }


    /**
     * Помощна функция за вземане на ключа за кеширан
     *
     * @param $userId
     *
     * @return string
     */
    protected static function getKeyName($userId)
    {

        return 'clipboard_' . $userId;
    }

    
    /**
     * Връща линк за експортиране във външната част
     *
     * @param int    $clsId
     * @param int    $objId
     * @param string $mid
     *
     * @return core_ET|NULL
     */
    public function getExternalExportLink($clsId, $objId, $mid)
    {

        return null;
    }


    /**
     * Добавя параметри към експорта на формата
     *
     * @param core_Form    $form
     * @param int          $clsId
     * @param int|stdClass $objId
     *
     * @return NULL|string
     */
    public function addParamFields($form, $clsId, $objId)
    {

    }
}
