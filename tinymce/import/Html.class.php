<?php


/**
 * Редактиране на HTML
 *
 * @category  bgerp
 * @package   tinymce
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tinymce_import_Html extends core_Mvc
{
    
    
    /**
     * Интерфейсни методи
     */
    public $interfaces = 'export_FileActionIntf, fileman_ConvertDataIntf';
    
    
    /**
     * Заглавие на модела
     */
    public $title = '';
    
    
    /**
     * Добавя бутон за обработка на файла
     *
     * @param core_Form $form
     * @param string    $fileHnd
     *
     * @see export_FileActionIntf
     */
    public function addActionBtn($form, $fileHnd)
    {
        if (!haveRole('user')) {
            
            return ;
        }
        
        if (!$fileHnd) {
            
            return ;
        }
        
        if (!is_string($fileHnd)) {
            
            return ;
        }
        
        $fRec = fileman::fetchByFh($fileHnd);
        
        if (!$fRec) {
            
            return ;
        }
        
        $ext = fileman::getExt($fRec->name);
        
        if ($ext == 'html' || $ext == 'xhtml') {
            $form->toolbar->addBtn('Редакция', array($this, 'editHtml', 'fileHnd' => $fileHnd, 'ret_url' => true), 'target=_blank, ef_icon = img/16/edit.png, title=Редакция на HTML файла');
        }
    }
    
    
    /**
     * Обработките на данните, за файла
     *
     * @param string $data
     *
     * @see fileman_ConvertDataIntf
     *
     * @return string
     */
    public function convertData($data)
    {
        $data = "<!DOCTYPE html><html><head><title>Document</title><meta charset=\"UTF-8\"></head><body style='font-family: Arial'>" . $data . '</body></html>';
        
        return $data;
    }
    
    
    /**
     * Екшън за редактиране на HTML файл
     *
     * @return Redirect|core_ET
     */
    public function act_editHtml()
    {
        requireRole('user');
        
        $fh = Request::get('fileHnd');
        
        expect($fRec = fileman::fetchByFh($fh));
        
        $ext = fileman::getExt($fRec->name);
        
        expect($ext === 'html' || $ext === 'xhtml' || $ext === 'txt', $ext);
        
        $retUrl = getRetUrl();
        
        if (empty($retUrl)) {
            $retUrl = array('fileman_Files', 'single', $fh);
        }
        
        $form = cls::get('core_Form');
        
        $html = 'html(tinyToolbars=fullscreen print, tinyFullScreen)';
        
        if (fileman_Buckets::canAddFileToBucket($fRec->bucketId)) {
            $urlArr = array('fileman_Files', 'updateFile', 'fileHnd' => $fh, 'dataType' => $this->className);
            $localUrl = toUrl($urlArr, 'local');
            $localUrl = urlencode($localUrl);
            
            $html = "html(tinyToolbars=fullscreen print save, tinyFullScreen, tinySaveCallback={$localUrl})";
        }
        
        $form->FNC('html', $html, 'input, caption=HTML', array('attr' => array('id' => 'editor')));
        $fContent = fileman::extractStr($fh);
        $form->setDefault('html', $fContent);
        
        $form->toolbar->addBtn('Файл', array('fileman_Files', 'single', $fh, 'ret_url' => true), 'ef_icon = fileman/icons/16/html.png, title=Преглед на файла');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        $form->title = 'Редактиране на HTML файл';
        
        // Сменяма wrapper'а да е празна страница
        Mode::set('wrapper', 'page_Empty');
        
        // Връщаме съдържанието
        return $form->renderHtml();
    }
}
