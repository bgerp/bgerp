<?php

cls::load('type_Varchar');
cls::load('fileman_Files');


/**
 * Клас 'fileman_FileType' -
 *
 *
 * @category  vendors
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class fileman_FileType extends type_Varchar {
    

    /**
     * Дължина на полето в mySql таблица
     */
    var $dbFieldLen = FILEMAN_HANDLER_LEN;

    /**
     * @todo Чака за документация...
     */
    function toVerbal($fh)
    {
        if(!$fh) return "";
        
        return fileman_Files::getLink($fh);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $Files = cls::get('fileman_Files');
        $Buckets = cls::get('fileman_Buckets');
        
        if($value) {
            $fileName = $Files->fetchByFh($value, 'name');
        }
        
        unset($attr['ondblclick']);
        
        $attrInp = $attr;
        $attrInp['id'] = $name . "_file_name_id";
        $attrInp['style'] = 'padding:5px;font-weight:bold;';
        
        if($fileName) {
            $crossImg = "<img src=" . sbf('img/16/delete.png') . " align=\"absmiddle\" title=\"" . tr("Премахване на файла") . "\" alt=\"\">";
            $html = $this->toVerbal($value) . "&nbsp;<a style=\"color:red;\" href=\"#\" onclick=\"unsetInputFile('" . $name . "')\">" . $crossImg . '</a>';
        }
        
        $tpl = ht::createElement("span", $attrInp, $html, TRUE);
        
        $tpl->append("<input name='{$name}' value='{$value}' id='{$name}_id' type='hidden'>");
        
        $bucket = $this->params['bucket'];
        
        $bucketId = $Buckets->fetchByName($bucket);
        
        expect($bucketId, 'Очаква се валидна кофа', $bucket);
        
        $tpl->prepend($Files->makeBtnToAddFile("+", $bucketId, 'setInputFile' . $name, array('class' => 'noicon', 'title' => 'Добавяне или промяна на файл')));
        
        $this->addJavascript($tpl, $name);
        
        return $tpl;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function addJavascript($tpl, $name)
    {
        $tpl->appendOnce("
            function setInputFile(name, fh, fName) {
                var divFileName = document.getElementById(name + '_file_name_id');
                var crossImg = '<img src=" . sbf('img/16/cross.png') . " align=\"absmiddle\" alt=\"\">';
                divFileName.innerHTML = getDownloadLink(fName, fh) + 
                '&nbsp;<a style=\"color:red;\" href=\"#\" onclick=\"unsetInputFile(\'' + name + '\')\">' + crossImg + '</a>';

                var inputFileHnd = document.getElementById(name + '_id');
                inputFileHnd.value = fh;
            }

            function getDownloadLink(fName, fh) {
                var url = '" . toUrl(array('fileman_Download', 'Download', 'fh' => '1')) . "';
                url = url.replace('1', fh);
                var link = '<a href=\"' + url + '\" target=\"_blank\">' + fName + '</a>';

                return link;
            }
        ", 'SCRIPTS');
        
        $tpl->appendOnce("
            function unsetInputFile(name) {
                var divFileName = document.getElementById(name + '_file_name_id');
                divFileName.innerHTML = '';

                var inputFileHnd = document.getElementById(name + '_id');
                inputFileHnd.value = '';
            }
        ", 'SCRIPTS');
        
        $tpl->appendOnce("
            function setInputFile{$name}(fh, fName)
            {
                return setInputFile('{$name}', fh, fName);
            }
        ", 'SCRIPTS');
    }
}