<?php


/**
 * Клас 'doc_plg_TransferDocs'
 *
 * Плъгин за за прехвурляне на документи от проектна папка към папка на потребител
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class doc_plg_TransferDoc extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        // Ако има уточнено поле към което да се трансферира, проверка дали е валидно
        if (isset($mvc->transferFolderField)) {
            expect($Type = $mvc->getFieldType($mvc->transferFolderField));
            expect($Type instanceof type_Key);
            
            // Полето трябва да е от тип ключ към корица на папка
            $typeMvc = $mvc->getFieldTypeParam($mvc->transferFolderField, 'mvc');
            expect(cls::haveInterface('doc_FolderIntf', $typeMvc));
        }

        setIfNot($mvc->allwaysAddCurrentUser, true);
    }
    
    
    /**
     * Преди запис на документ
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        if (($mvc->allwaysAddCurrentUser && (array_key_exists('sharedUsers', (array)$rec))) || empty($rec->id)) {
            if ($mvc->getField('sharedUsers', false)) {
                $userId = crm_Profiles::fetchField(array("#personId = '[#1#]'", $rec->personId), 'userId');
                if ($userId) {
                    $rec->sharedUsers = keylist::addKey($rec->sharedUsers, $userId);
                }
                if ($rec->alternatePersons) {
                    foreach (type_Keylist::toArray($rec->alternatePersons) as $aPerson) {
                        $alternatePersonId = crm_Profiles::fetchField(array("#personId = '[#1#]'", $aPerson), 'userId');
                        if ($alternatePersonId) {
                            $rec->sharedUsers = keylist::addKey($rec->sharedUsers, $alternatePersonId);
                        }
                    }
                }

                $cu = core_Users::getCurrent();
                $rec->sharedUsers = keylist::addKey($rec->sharedUsers, $cu);
            }
        }
    }
    
    
    /**
     * Ако в документа няма код, който да рутира документа до папка/тред,
     * долния код, рутира документа до "Несортирани - [заглавие на класа]"
     */
    protected static function on_BeforeRoute($mvc, &$res, $rec)
    {
        // Ако е събмитнато поле към което да се трансферира
        if (isset($rec->{$mvc->transferFolderField})) {
            $coverId = $rec->{$mvc->transferFolderField};
            
            // Форсира се папката на обекта, документа ще се създаде там
            $CoverMvc = cls::get($mvc->getFieldTypeParam($mvc->transferFolderField, 'mvc'));
            $rec->folderId = $CoverMvc->forceCoverAndFolder($coverId);
        }
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
        // Споделяме текущия потребител със нишката на документа, за всеки случай
        $cu = core_Users::getCurrent();
        doc_ThreadUsers::addShared($rec->threadId, $rec->containerId, $cu);
    }
}
