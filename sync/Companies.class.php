<?php


/**
 * Синхронизиране на фирми между bgERP системи
 *
 *
 * @category  bgerp
 * @package   synck
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2020 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Синхронизиране на фирми между bgERP системи
 */
class sync_Companies extends sync_Helper
{
    /**
     * Какво друго да експортираме?
     */
    public $exportAlso = array(
            'crm_Companies' => array(
                    array('crm_Locations' => 'contragentCls|contragentId'),
                    array('bank_Accounts' => 'contragentCls|contragentId'),
                    array('cond_ConditionsToCustomers' => 'cClass|cId'),
                    array('price_ListToCustomers' => 'cClass|cId'),
                    array('crm_ext_Cards' => 'companyId'),
            ),
            
            'cat_Listings' => array(
                    array('cat_ListingDetails' => 'listId'),
            ),

            'crm_Locations' => array(
                    array('sales_Routes' => 'locationId'),
            ),

            'price_Lists' => array(
                    array('price_ListRules' => 'listId'),
            ),

            'cat_Products' => array(
                    array('cat_products_Packagings' => 'productId'),
                    array('cat_products_Params' => 'classId|productId'),
            ),

            'crm_Persons' => array(
                array('crm_ext_Cards' => 'personId'),
            ),
    );
    
    
    /**
     *  Връща Json-a на филтрираните обекти
     */
    public function act_Export()
    {
        $this->requireRight('export', true);
        sync_Settings::guardExportRun(
            sync_Settings::getRequestSettings(false, true)
        );

        expect(core_Packs::isInstalled('crm'));

        core_App::setTimeLimit(1000);

        $res = array();

        core_Users::forceSystemUser();
        try {
            self::collectExport($res);
        } finally {
            core_Users::cancelSystemUser();
        }

        return $this->outputRes($res);
    }


    /**
     * Събира фирмите (по настроените в sync_Settings групи) в масива за експорт
     *
     * @param array         $res
     * @param stdClass|null $controller
     */
    public static function collectExport(&$res, $controller = null)
    {
        $settingsRec = sync_Settings::getRequestSettings(true, true);
        $mode = sync_Settings::getExportMode($settingsRec, 'companies');
        if ($mode == 'none') {

            return;
        }

        $me = $controller ?: cls::get(get_called_class());

        $cQuery = crm_Companies::getQuery();
        if ($mode == 'selected') {
            if (empty($settingsRec->companiesGroups)) {

                return;
            }

            plg_ExpandInput::applyExtendedInputSearch('crm_Companies', $cQuery, $settingsRec->companiesGroups);
        }

        while ($rec = $cQuery->fetch()) {
            sync_Map::exportRec('crm_Companies', $rec->id, $res, $me);

            if ($rec->folderId) {
                $lQuery = cat_Listings::getQuery();
                $lQuery->where(array("#state = 'active' AND #folderId = [#1#]", $rec->folderId));
                while ($lRec = $lQuery->fetch()) {
                    $lRec->_companyId = $rec->id;
                    sync_Map::exportRec('cat_Listings', $lRec, $res, $me);
                }

                if (core_Packs::isInstalled('colab')) {
                    $pQuery = colab_FolderToPartners::getQuery();
                    $pQuery->where(array("#folderId = [#1#]", $rec->folderId));

                    while ($pRec = $pQuery->fetch()) {
                        $pRec->_companyId = $rec->id;
                        sync_Map::exportRec('colab_FolderToPartners', $pRec, $res, $me);
                    }
                }
            }
        }
    }
    
    
    /**
     * Вика се от act_Import
     */
    public static function import($update = true)
    {
        $resArr = self::getDataFromUrl(get_called_class());

        sync_Settings::importData($resArr, $update);
    }


    /**
     * Подготвя дедупликацията преди който и да е recursive company import
     */
    public static function on_BeforeSyncImportAll($mvc, &$resArr, $controller, $update = null)
    {
        cls::get('crm_Companies')->dbIndexes['uicId'] = (object) array(
            'fields' => 'uicId',
            'type' => 'UNIQUE',
        );
    }


    /**
     * Специфична за фирмите/листингите обработка преди импортиране на отделен запис.
     *
     * Извиква се като събитие от обединения импорт @see sync_Settings::importData.
     * Задаването на $skip = true пропуска импортирането на записа.
     *
     * @param core_Mvc $mvc
     * @param array    $resArr
     * @param string   $class
     * @param int      $id
     * @param stdClass $rec
     * @param bool     $skip
     * @param core_Mvc $controller
     * @param bool     $update
     */
    public static function on_BeforeSyncImportRec($mvc, &$resArr, $class, $id, $rec, &$skip, $controller, $update = null)
    {
        // Ако има списък в приемника, не импортираме листинга
        if ($class == 'cat_ListingDetails') {
            foreach ((array) $resArr['cat_Listings'] as $cDetKey => $cDetArr) {
                if (($cDetKey == $rec->listId) && $cDetArr->_companyId) {
                    $cId = sync_Map::importRec('crm_Companies', $cDetArr->_companyId, $resArr, $controller, $update);
                    if (cond_Parameters::getParameter('crm_Companies', $cId, 'salesList')) {
                        unset($resArr['cat_Listings'][$cDetKey]);
                        unset($resArr['cat_ListingDetails'][$id]);
                        $skip = true;
                    }
                }
            }
        }

        if ($class == 'cat_Listings') {
            if (!empty($rec->_companyId)) {
                $cId = sync_Map::importRec('crm_Companies', $rec->_companyId, $resArr, $controller, $update);
                if (cond_Parameters::getParameter('crm_Companies', $cId, 'salesList')) {
                    foreach ((array) $resArr['cat_ListingDetails'] as $cDetKey => $cDetArr) {
                        if ($cDetArr->listId == $id) {
                            unset($resArr['cat_ListingDetails'][$cDetKey]);
                        }
                    }

                    unset($resArr['cat_Listings'][$id]);
                    $skip = true;
                }
            }
        }
    }


    /**
     * Финализиране на импорта - изчистване на осиротелите листинги и преброяване на групите.
     *
     * Извиква се като събитие от обединения импорт @see sync_Settings::importData.
     *
     * @param core_Mvc $mvc
     * @param array    $resArr
     */
    public static function on_AfterSyncImportAll($mvc, &$resArr)
    {
        if (array_key_exists('cat_Listings', $resArr) || array_key_exists('cat_ListingDetails', $resArr)) {
            cat_ListingDetails::delete("#productId = 0");
        }

        if (array_key_exists('crm_Persons', $resArr)) {
            crm_Groups::updateGroupsCnt('crm_Persons', 'personsCnt');
        }
    }


    /**
     * Синхронизира двете системи
     */
    public function act_Import()
    {
        $this->requireRight('import');

        ini_set('memory_limit', '2048M');

        expect(core_Packs::isInstalled('crm'));

        core_App::setTimeLimit(1000);

        $update = (Request::get('update') == 'none') ? false : true;

        core_Users::forceSystemUser();
        try {

            return $this->import($update);
        } finally {
            core_Users::cancelSystemUser();
        }
    }
}
