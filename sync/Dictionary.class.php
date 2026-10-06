<?php


/**
 * Синхронизиране на речника от replace пакета
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
 * @title     Синхронизиране на речника от replace пакета
 */
class sync_Dictionary extends sync_Helper
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

        expect(core_Packs::isInstalled('replace'));
        
        core_App::setTimeLimit(100);
        
        $res = array();
        
        core_Users::forceSystemUser();
        try {
            self::collectExport($res, $this);
        } finally {
            core_Users::cancelSystemUser();
        }

        return self::outputRes($res);
    }


    /**
     * Добавя речника към общия export traversal, ако е разрешен за клиента
     *
     * @param array         $res
     * @param stdClass|null $controller
     */
    public static function collectExport(&$res, $controller = null)
    {
        $settingsRec = sync_Settings::getRequestSettings(true, true);
        if (sync_Settings::getExportMode($settingsRec, 'dictionary') != 'all') {

            return;
        }

        expect(core_Packs::isInstalled('replace'));
        $me = $controller ?: cls::get(get_called_class());
        $dQuery = replace_Dictionary::getQuery();

        while ($rec = $dQuery->fetch()) {
            sync_Map::exportRec('replace_Dictionary', $rec->id, $res, $me);
        }
    }


    /**
     * Синхронизира двете системи
     */
    public function act_Import()
    {
        self::requireRight('import');
        
        expect(core_Packs::isInstalled('replace'));
        
        core_App::setTimeLimit(100);
        // Ръчният import може да върви успоредно с други; вдигаме лимита,
        // защото payload-ът се държи целият в паметта.
        ini_set('memory_limit', '2048M');
        
        $resArr = self::getDataFromUrl(get_called_class());
        
        core_Users::forceSystemUser();
        try {
            sync_Settings::importData($resArr, true);
        } finally {
            core_Users::cancelSystemUser();
        }
    }
}
