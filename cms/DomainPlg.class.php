<?php


/**
 * Клас 'cms_DomainPlg' - Добавя поле за избор на домейн в други модели
 *
 *
 * @category  bgerp
 * @package   cms
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cms_DomainPlg extends core_Plugin
{
    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    public function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $form = $data->listFilter;
        
        // В хоризонтален вид
        $form->view = 'horizontal';
        
        // Добавяме бутон
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Автоматично обовяване
        $form->setField('domainId', 'autoFilter');
        
        // Показваме само това поле. Иначе и другите полета
        // на модела ще се появят
        $form->showFields = 'domainId';
        
        $form->input();
        
        if ($form->isSubmitted() && $domainId = Request::get('domainId', 'int')) {
            cms_Domains::selectCurrent($domainId);
        }
        
        cms_Domains::setFormField($form);
        
        $domainId = cms_Domains::getCurrent();
        
        $data->query->where("#domainId = {$domainId}");
    }
    
    
    /**
     * Дава възможност за избор само между достъпните домейни
     */
    public function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        cms_Domains::setFormField($data->form);
    }
    
    
    public static function on_AfterInputEditForm($mvc, $form)
    {
        if ($form->isSubmitted() && $domainId = $form->rec->domainId) {
            cms_Domains::selectCurrent($domainId);
        }
    }
}
