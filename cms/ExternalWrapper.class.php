<?php


/**
 * Клас 'cms_ExternalWrapper'
 *
 * Обвивка за външни потребители
 *
 *
 * @category  bgerp
 * @package   cms
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cms_ExternalWrapper extends plg_ProtoWrapper
{
    /**
     * HTML клас за табовете на обвивката
     */
    protected $htmlClass = 'foldertabs';
    
    
    /**
     * Описание на табовете
     */
    public function description()
    {
        $orderedTabs = array();

        // Извличане на наличните блокове
        $tabBlocks = core_Classes::getOptionsByInterface('colab_BlockIntf');
        foreach ($tabBlocks as $className){
            $Intf = cls::getInterface('colab_BlockIntf', $className);
            if($Intf->displayTab()){
                $orderedTabs[$Intf->getTabOrder()] = $Intf;
            }
        }
        ksort($orderedTabs);

        // Показването им в таба
        foreach ($orderedTabs as $tabIntf){
            $tabUrl = $tabIntf->getBlockTabUrl();
            $this->TAB($tabUrl, $tabIntf->getBlockTabName(), 'partner');
        }
    }
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    public function on_AfterRenderWrapping($invoker, &$tpl, $blankTpl, $data = null)
    {
        static $i;
        $i++;
        if ($i > 1) {
            
            return;
        }
        
        // Рендиране на обвивката от бащата
        Mode::push('externalWrapper', true);
        parent::on_AfterRenderWrapping($invoker, $tpl, $blankTpl, $data);
        
        // Обграждаме обвивката със div
        if ($tpl instanceof core_ET) {
            $tpl->prepend("<div class = 'contractorExtHolder'>");
            $tpl->append('</div>');
        }
        Mode::pop('externalWrapper');
    }
}
