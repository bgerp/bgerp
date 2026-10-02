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
        setPartIfNot($mvc, 'sharableToContentSourceClass', $mvc->className);
        setPartIfNot($mvc, 'contentMenuFld', 'menuId');
        setPartIfNot($mvc, 'contentMenuSharedFld', 'sharedMenus');
        setPartIfNot($mvc, 'contentMenuFromAllDomains', false);

        if (empty($mvc->fields[$mvc->contentMenuFld])) {
            $mvc->FLD($mvc->contentMenuFld, 'key(mvc=cms_Content,select=menu, allowEmpty)', 'caption=Меню->Основно,silent,refreshForm,mandatory');
        }

        if (empty($mvc->fields[$mvc->contentMenuSharedFld])) {
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

        // Домейнът е на избраното меню, а без него - текущият, ако има
        $domainId = null;
        if (empty($mvc->contentMenuFromAllDomains)) {
            $menuId = $rec->{$mvc->contentMenuFld} ?? null;
            $domainId = !empty($menuId) ? cms_Content::fetchField($menuId, 'domainId') : cms_Domains::getCurrent('id', false);
        }
        $currentMenuOpt = cms_Content::getMenuOpt($mvc->sharableToContentSourceClass, !empty($domainId) ? $domainId : null);
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


    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    public function on_AfterPrepareListFilter($mvc, $data)
    {
        $form = $data->listFilter;

        // В хоризонтален вид
        $form->view = 'horizontal';

        // Добавяме бутон
        $domainId = cms_Domains::inputListFilterField($form);
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');

        // Показваме само това поле. Иначе и другите полета
        // на модела ще се появят
        if($mvc->hasPlugin('plg_Search')){
            $form->showFields = "search, {$mvc->contentMenuFld}, domainId";
            $form->input("search, {$mvc->contentMenuFld}", "silent");
        } else {
            $form->showFields = "{$mvc->contentMenuFld}, domainId";
            $form->input("{$mvc->contentMenuFld}", "silent");
        }

        $menuOptions = cms_Content::getMenuOpt($mvc->sharableToContentSourceClass, $domainId);
        $form->setOptions($mvc->contentMenuFld, $menuOptions);
        $form->setField($mvc->contentMenuFld, 'refreshForm');

        if (countR($menuOptions) == 0) {
            if (empty($domainId)) {
                redirect(array('cms_Content'), false, 'Моля въведете поне една точка от менюто с източник');
            }
            
            // Домейн без такова меню - празен списък
            $data->query->where('1=2');
            
            return;
        }

        if (($form->rec->{$mvc->contentMenuFld} ?? null) && !($menuOptions[$form->rec->{$mvc->contentMenuFld}] ?? null)) {
            $form->rec->{$mvc->contentMenuFld} = key($menuOptions);
        }

        if (countR($menuOptions) && !$form->isSubmitted() && !empty($domainId)) {
            $form->rec->{$mvc->contentMenuFld} = key($menuOptions);
        }

        if (!empty($form->rec->{$mvc->contentMenuFld})) {
            $data->query->where(array("#{$mvc->contentMenuFld} = '[#1#]' OR #{$mvc->contentMenuSharedFld} LIKE '%|[#1#]|%'", $form->rec->{$mvc->contentMenuFld}));
            $data->query->XPR('_isShared', 'enum(no,yes)', "(CASE #menuId WHEN {$form->rec->{$mvc->contentMenuFld}} THEN 'no' ELSE 'yes' END)");
        }

        $data->query->orderBy('#menuId');
    }
}