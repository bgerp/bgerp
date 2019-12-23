<?php


/**
 *
 *
 * @category  bgerp
 * @package   colab
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class colab_plg_Document extends core_Plugin
{
    /**
     * След пдоготовка на сингъла
     */
    public static function on_AfterPrepareSingle($mvc, $res, $data)
    {
        // Ако е контрактор, маркираме документа като видян
        if (core_Users::haveRole('partner') && !Mode::get('eshopFinalize') && !Mode::get('getLinkedObj')) {
            colab_DocumentLog::markAsViewed($data->rec->containerId);
        }
    }
    
    
    /**
     * След подготовка на вербалното показване
     */
    public static function on_AfterRecToVerbal(&$invoker, &$row, &$rec, $fields = array())
    {
        if ($fields && $fields['-single']) {
            if (!Mode::is('text', 'xhtml') && !Mode::is('printing') && core_Users::isPowerUser() && colab_FolderToPartners::fetch("#folderId = '{$rec->folderId}'")) {
                $isVisible = false;
                if ($rec->containerId) {
                    $cRec = doc_Containers::fetch($rec->containerId);
                    if ($cRec->visibleForPartners == 'yes') {
                        $isVisible = true;
                    }
                } else {
                    if ($invoker->isVisibleForPartners($rec)) {
                        $isVisible = true;
                    }
                }
                
                if ($isVisible) {
                    
                    // Може и да се провери стойноста на `visibleForPartners` в `doc_Containers`
                    
                    $link = colab_DocumentLog::renderViewedLink($rec->containerId);
                    
                    $row->DocumentSettings = new ET($row->DocumentSettings);
                    
                    $row->DocumentSettings->append($link);
                    
                    jquery_Jquery::runAfterAjax($row->DocumentSettings, 'showTooltip');
                }
            }
        }
    }
    
    
    /**
     *
     *
     * @param core_Master  $mvc
     * @param NULL|array   $res
     * @param int|stdClass $id
     */
    public static function on_AfterGetSingleUrlArray($mvc, &$res, $id)
    {
        if (!isset($res) || (is_array($res) && empty($res))) {
            $rec = $mvc->fetchRec($id);
            if ($rec->threadId && colab_Threads::haveRightFor('single', doc_Threads::fetch($rec->threadId))) {
                $res = array($mvc, 'single', $rec->id, 'ret_url' => true);
            }
        }
    }
}
