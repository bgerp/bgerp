<?php


/**
 * Синхронизиране на лица между bgERP системи
 *
 *
 * @category  bgerp
 * @package   synck
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2020 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Синхронизиране на лица между bgERP системи
 */
class sync_Persons extends sync_Helper
{
    
    
    /**
     *  Връща Json-a на филтрираните обекти
     */
    public function act_Export()
    {
        self::requireRight('export', true);
        sync_Settings::guardExportRun(
            sync_Settings::getRequestSettings(false, true)
        );

        core_App::setTimeLimit(1000);

        $res = array();

        core_Users::forceSystemUser();
        try {
            self::collectExport($res);
        } finally {
            core_Users::cancelSystemUser();
        }

        return self::outputRes($res);
    }


    /**
     * Събира лицата (по настроените в sync_Settings групи) в масива за експорт
     *
     * @param array         $res
     * @param stdClass|null $controller
     */
    public static function collectExport(&$res, $controller = null)
    {
        $settingsRec = sync_Settings::getRequestSettings(true, true);
        $mode = sync_Settings::getExportMode($settingsRec, 'persons');
        if ($mode == 'none') {

            return;
        }

        $me = $controller ?: cls::get(get_called_class());

        $query = crm_Persons::getQuery();

        if ($mode == 'selected') {
            if (empty($settingsRec->personsGroups)) {

                return;
            }

            plg_ExpandInput::applyExtendedInputSearch('crm_Persons', $query, $settingsRec->personsGroups);
        }

        while ($rec = $query->fetch()) {
            sync_Map::exportRec('crm_Persons', $rec->id, $res, $me);

            $pRec = crm_Profiles::fetch("#personId = {$rec->id}");
            if ($pRec) {
                sync_Map::exportRec('crm_Profiles', $pRec->id, $res, $me);
            }

            if ($rec->folderId && core_Packs::isInstalled('colab')) {
                $pQuery = colab_FolderToPartners::getQuery();
                $pQuery->where(array("#folderId = [#1#]", $rec->folderId));

                while ($cRec = $pQuery->fetch()) {
                    $cRec->_personId = $rec->id;
                    sync_Map::exportRec('colab_FolderToPartners', $cRec, $res, $me);
                }
            }
        }
    }


    /**
     * Синхронизира двете системи
     */
    public function act_Import()
    {
        self::requireRight('import');
        
        core_App::setTimeLimit(1000);
        // Ръчният import може да върви успоредно с други; вдигаме лимита,
        // защото payload-ът се държи целият в паметта.
        ini_set('memory_limit', '2048M');

        $update = (Request::get('update') == 'none') ? false : true;

        $resArr = self::getDataFromUrl(get_called_class());

        core_Users::forceSystemUser();
        try {
            sync_Settings::importData($resArr, $update);
        } finally {
            core_Users::cancelSystemUser();
        }

        return;
    }
}
