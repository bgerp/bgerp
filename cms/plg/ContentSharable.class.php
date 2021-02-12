<?php


/**
 * Клас 'cms_plg_ContentSharable' - За споделяне на модели към менюта
 *
 * @category  bgerp
 * @package   cms
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cms_plg_ContentSharable extends core_Plugin
{


    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setIfNot($mvc->sharableToContentSourceClass, $mvc->className);
        setIfNot($mvc->contentMenuFld, 'menuId');
        setIfNot($mvc->contentMenuSharedFld, 'sharedMenus');

        if (!$mvc->fields[$mvc->contentMenuFld]) {
            $mvc->FLD($mvc->contentMenuFld, 'key(mvc=cms_Content,select=menu, allowEmpty)', 'caption=Меню->Основно,silent,refreshForm,mandatory');
        }

        if (!$mvc->fields[$mvc->contentMenuSharedFld]) {
            $mvc->FLD($mvc->contentMenuSharedFld, 'keylist(mvc=cms_Content,select=menu, allowEmpty)', 'caption=Меню->Споделяне в,silent,refreshForm');
        }
    }


    /**
     * Изпълнява се след подготовката на формата за единичен запис
     */
    protected function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        $domainId = cms_Domains::getCurrent();
        $currentMenuOpt = cms_Content::getMenuOpt($mvc->sharableToContentSourceClass, $domainId);
        $sharedMenuOpt = cms_Content::getMenuOpt($mvc->sharableToContentSourceClass);

        if (isset($rec->{$mvc->contentMenuFld})){
            unset($sharedMenuOpt[$rec->{$mvc->contentMenuFld}]);

            if(isset($rec->id)){
                if(!array_key_exists($rec->{$mvc->contentMenuFld}, $currentMenuOpt)){
                    $currentMenuOpt[$rec->{$mvc->contentMenuFld}] = cms_Content::getVerbal($rec->{$mvc->contentMenuFld}, 'menu');
                }
            }
        }

        $form->setSuggestions($mvc->contentMenuSharedFld, $sharedMenuOpt);
        if (countR($currentMenuOpt) == 1) {
            $form->setReadOnly($mvc->contentMenuFld);
        }

        $form->setOptions($mvc->contentMenuFld, $currentMenuOpt);
    }
}