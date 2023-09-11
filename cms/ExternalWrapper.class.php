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
        $tabBlocks = core_Classes::getOptionsByInterface('colab_BlockIntf');
        foreach ($tabBlocks as $className){
            $Intf = cls::getInterface('colab_BlockIntf', $className);
            if($Intf->displayTab()){
                $orderedTabs[$Intf->getTabOrder()] = $Intf;
            }
        }
        ksort($orderedTabs);

        foreach ($orderedTabs as $tabIntf){
            $tabUrl = $tabIntf->getBlockTabUrl();
            $this->TAB($tabUrl, $tabIntf->getBlockTabName(), 'partner');
        }

       // bp($orderedTabs);

        return;



        if (core_Packs::isInstalled('colab')) {
            if (core_Users::haveRole('partner')) {
                $this->getContractorTabs();
            }
        }
        

    }
    
    
    /**
     * Какви табове да се добавят ако потребителя е контрактор
     */
    public function getContractorTabs_()
    {
        $threadsUrl = $containersUrl = array();
        
        $threadId = Request::get('threadId', 'int');
        $folderId = Request::get('folderId', 'key(mvc=doc_Folders,select=title)');
        
        if (colab_Folders::getSharedFoldersCount() > 1) {
            $this->TAB('colab_Folders', 'Папки', 'partner');
        } else {
            $query = colab_Folders::getQuery();
            $folderId = $query->fetch()->id;
        }
        
        if (!$folderId) {
            $folderId = Mode::get('lastFolderId');
        } else {
            Mode::setPermanent('lastFolderId', $folderId);
        }
        
        if ($folderId && colab_Threads::haveRightFor('list', (object) array('folderId' => $folderId))) {
            $threadsUrl = array('colab_Threads', 'list', 'folderId' => $folderId);
        }
        
        if (!$threadId) {
            $threadId = Mode::get('lastThreadId');
        } else {
            Mode::setPermanent('lastThreadId', $threadId);
        }
        
        if ($threadId) {
            $threadRec = doc_Threads::fetch($threadId);
            if (colab_Threads::haveRightFor('single', $threadRec)) {
                $containersUrl = array('colab_Threads', 'single', 'threadId' => $threadId);
            }
        }
        
        $this->TAB($threadsUrl, 'Теми', 'partner');
        $this->TAB($containersUrl, 'Нишка', 'partner');
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
        parent::on_AfterRenderWrapping($invoker, $tpl, $blankTpl, $data);
        
        // Обграждаме обвивката със div
        if ($tpl instanceof core_ET) {
            $tpl->prepend("<div class = 'contractorExtHolder'>");
            $tpl->append('</div>');
        }
    }
}
