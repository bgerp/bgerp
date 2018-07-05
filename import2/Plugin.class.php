<?php


/**
 * Интерфейс за импортиране на данни в детайл
 *
 *
 * @category  bgerp
 * @package   import2
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Плъгин за импортиране на данни в мениджър
 */
class import2_Plugin extends core_Plugin
{
    
    /**
     * Извиква се преди изпълняването на екшън
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
        if ($action != 'import2' || empty($mvc->importInterface)) {
            return;
        }
        
        $mvc->requireRightFor('import2');
        
        $form = cls::get('core_Form');
        $rec = &$form->rec;
        $form->FLD('driverClass', 'class(interface=' . $mvc->importInterface . ',select=title)', 'silent,removeAndRefreshForm,caption=Източник,mandatory');

        if (isset($mvc->masterKey)) {
            $form->FLD($mvc->masterKey, 'int', 'silent,input=hidden');
        }

        $form->input('', 'silent');
        
        $mvc->requireRightFor('import2', $rec);
        
        if ($mvc->Master && isset($mvc->masterKey, $rec->{$mvc->masterKey})) {
            $masterId = $rec->{$mvc->masterKey};
            $title = $mvc->Master->getFormTitleLink($masterId);
        } else {
            $title = $mvc->title;
            $masterId = null;
        }

        $form->title = 'Импорт на записи в|* <b>' . $title . '</b>';
        
        $opt = self::getDriverOptions($mvc, $masterId);
        
        if (count($opt) == 1) {
            $rec->driverClass = key($opt);
        } elseif (count($opt) > 1) {
            $opt = array('' => '') + $opt;
        }
        
        $form->setOptions('driverClass', $opt);

        // Ако има избран драйвер
        if (isset($rec->driverClass)) {
            $Driver = cls::getInterface('import2_DriverIntf', $rec->driverClass);
            
            // Добавят се полетата от него
            $Driver->addImportFields($mvc, $form);
            $form->input(null, 'silent');
            $refreshFields = arr::make(array_keys($form->selectFields()), true);
            unset($refreshFields['driverClass'], $refreshFields['noteId']);
            $refreshFieldsString = implode('|', $refreshFields);
            $form->setField('driverClass', "removeAndRefreshForm={$refreshFieldsString}");
            $Driver->prepareImportForm($mvc, $form);
            
            // Инпут и проверка на формата
            $form->input();
            if ($form->isSubmitted()) {
                $Driver->checkImportForm($mvc, $form);
            }
            // Ако е събмитната формата
            if ($form->isSubmitted()) {
                    
                // Опит за подготовка на записите за импорт
                $status = $Driver->doImport($mvc, $rec);
     
                if (!$form->gotErrors()) {
                    redirect(getRetUrl(), false, $status);
                }
            }
        }
        
        // Добавяне на бутони
        $form->toolbar->addSbBtn('Импорт', 'save', 'ef_icon = img/16/import.png, title=Импорт');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        // Рендиране на формата
        $res = $mvc->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($res, $form);
            
        // ВАЖНО: спираме изпълнението на евентуални други плъгини
        return false;
    }
    
    
    /**
     * Изпълнява се след подготвянето на тулбара в листовия изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        // Бутон за импорт в лист изгледа
        
        $url = array($mvc, 'import2', 'ret_url' => true);
        $rec = new stdClass();
        if ($data->masterId) {
            $url[$mvc->masterKey] = $data->masterId;
            $rec->{$mvc->masterKey} = $data->masterId;
        }
        if ($mvc->haveRightFor('import2', $rec)) {
            $data->toolbar->addBtn('Импорт', $url, null, 'title=Импортиране на записи,ef_icon=img/16/import.png');
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'import2') {
            $requiredRoles = $mvc->getRequiredRoles('add', $rec, $userId);

            if (empty($mvc->importInterface)) {
                $requiredRoles = 'no_one';

                return;
            }

            $masterId = null;
            if ($masterKey = $mvc->masterKey) {
                $masterId = $rec->{$masterKey};
            }

            $opt = self::getDriverOptions($mvc, $masterId, $userId);

            if (!count($opt)) {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Връща опциите за драйвера
     *
     * @param core_Manager $mvc      - клас в който ще се импортира
     * @param int|NULL     $masterId - ако импортираме в детайл, id на мастъра му
     * @param int|NULL     $userId   - ид на потребител
     *
     * @return array - масив с драйвери, които могат да бъдат избрани
     */
    private static function getDriverOptions($mvc, $masterId = null, $userId = null)
    {
        $opt = core_Classes::getOptionsByInterface($mvc->importInterface, 'title');
        
        if ($userId === null) {
            $userId = core_Users::getCurrent();
        }

        foreach ($opt as $id => $title) {
            $Driver = cls::getInterface('import2_DriverIntf', $id);
            if (!$Driver->canSelectDriver($mvc, $masterId, $userId)) {
                unset($opt[$id]);
            }
        }

        reset($opt);

        return $opt;
    }
}
