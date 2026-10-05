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
        
        // Без текущ домейн се показват всички, вместо да се иска избор
        $domainId = cms_Domains::inputListFilterField($form);
        if (!empty($domainId)) {
            $data->query->where("#domainId = {$domainId}");
        } else {
            $data->listFields['domainId'] = 'Домейн';
        }
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
        $domainId = $form->rec->domainId ?? null;
        if ($form->isSubmitted() && !empty($domainId)) {
            cms_Domains::selectCurrent($domainId);
        }
    }



    /**
     * Домейнът в списъка е линк към него
     */
    public static function on_AfterPrepareListRows($mvc, &$res, $data)
    {
        if (empty($data->listFields['domainId']) || !countR($data->rows ?? null)) {

            return;
        }

        foreach ($data->rows as $id => $row) {
            $domainId = $data->recs[$id]->domainId ?? null;
            if (!empty($domainId)) {
                $row->domainId = cms_Domains::getHyperlink($domainId, true);
            }
        }
    }
}
