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


    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    public function on_AfterPrepareListFilter($mvc, $data)
    {
        $form = $data->listFilter;

        // В хоризонтален вид
        $form->view = 'horizontal';

        // Добавяме бутон
        $domains = cms_Domains::getDomainOptions(false, core_Users::getCurrent());
        $form->FLD('domainId', 'key(mvc=cms_Domains,select=titleExt)', 'caption=Домейн,silent,autoFilter,forceField');
        if (countR($domains) == 1) {
            $form->setField('domainId', 'input=hidden');
        } else {
            $form->setOptions('domainId', $domains);
        }

        $form->setDefault('domainId', cms_Domains::getCurrent());
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');

        // Показваме само това поле. Иначе и другите полета
        // на модела ще се появят
        if($mvc->hasPlugin('plg_Search')){
            $form->showFields = "search, {$mvc->contentMenuFld}, domainId";
            $form->input("search, {$mvc->contentMenuFld}, domainId", "silent");
        } else {
            $form->showFields = "{$mvc->contentMenuFld}, domainId";
            $form->input("{$mvc->contentMenuFld}, domainId", "silent");
        }

        cms_Domains::selectCurrent($form->rec->domainId);
        $menuOptions = cms_Content::getMenuOpt($mvc->sharableToContentSourceClass, $form->rec->domainId);
        $form->setOptions($mvc->contentMenuFld, $menuOptions);
        $form->setField($mvc->contentMenuFld, 'refreshForm');

        if (countR($menuOptions) == 0) {
            redirect(array('cms_Content'), false, 'Моля въведете поне една точка от менюто с източник');
        }

        if ($form->rec->{$mvc->contentMenuFld} && !$menuOptions[$form->rec->{$mvc->contentMenuFld}]) {
            $form->rec->{$mvc->contentMenuFld} = key($menuOptions);
        }

        if (countR($menuOptions) && !$form->isSubmitted()) {
            $form->rec->{$mvc->contentMenuFld} = key($menuOptions);
        }

        if ($form->rec->menuId) {
            $data->query->where(array("#{$mvc->contentMenuFld} = '[#1#]' OR #{$mvc->contentMenuSharedFld} LIKE '%|[#1#]|%'", $form->rec->{$mvc->contentMenuFld}));
            $data->query->XPR('_isShared', 'enum(no,yes)', "(CASE #menuId WHEN {$form->rec->{$mvc->contentMenuFld}} THEN 'no' ELSE 'yes' END)");
        }

        $data->query->orderBy('#menuId');
    }
}