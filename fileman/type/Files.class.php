<?php


/**
 * Тип за добавяне за качване на няколко файла едновременно
 * 
 * @category  bgerp
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_type_Files extends type_Keylist
{
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        $params['params']['mvc'] = 'fileman_Files';
        
        setIfNot($params['params']['select'], 'name');
        
        parent::init($params);
    }
    
    
    /**
     * Конвертира стойността от вербална към (int)  
     * 
     * @param mixed $value
     * 
     * @see type_Keylist::fromVerbal_()
     * 
     * @return mixed
     */
    function fromVerbal_($value)
    {
        $nVal = arr::make($value);
        
        $rVal = array();
        
        foreach ($nVal as $fileHnd) {
            $fId = fileman_Files::fetchByFh($fileHnd, 'id');
            
            if (!$fId) continue;
            
            $rVal[$fId] = $fId;
        }
        
        return parent::fromVerbal_($rVal);
    }
    

    
    /**
     * @todo Чака за документация...
     */
    function toVerbal($fhList)
    {   
        if(fileman_Files2::isFileHnd($fhList)) {
            $fhList = '|' . fileman_Files2::fhToId($fhList) . '|';
        }

        $fhArr = $this->toArray($fhList);
      
        $res = '';
        
        foreach ($fhArr as $id) {
            $fh = fileman_Files::fetchField($id, 'fileHnd');
            $res .= fileman_Files::getLink($fh);
        }
        if (!$res) return "";
        
        $align = $this->params['align'] ? $this->params['align'] : 'horizontal';
        $align = 'align_' . $align;
        $res = "<span class='{$align}' style='padding-bottom:5px'>" . $res . "</span>";
         
        return $res;
    }
    
    
    /**
     * 
     * 
     * @param string $name
     * @param string $value
     * @param array $attr
     * 
     * @see type_Keylist::renderInput_()
     * 
     * @return core_ET
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $Files = cls::get('fileman_Files');
        
        unset($attr['ondblclick']);
        
        $attrInp = $attr;
        $attrInp['id'] = $name . "_files_name_id";
        $align = $this->params['align'] ? $this->params['align'] : 'horizontal';
        $attrInp['class'] .= $attr['class'] . ' input_align_' . $align;
        
        $valueFhArr = array();
        if(fileman_Files2::isFileHnd($value)) {
            $value = '|' . fileman_Files2::fhToId($value) . '|';
        }
        if ($value && (strpos($value, '|') !== FALSE)) {
            $valueArr = $this->toArray($value);
            
            foreach ($valueArr as $vId) {
                $fRec = fileman_Files::fetch($vId);
                
                $valueFhArr[$fRec->fileHnd] = $fRec->fileHnd;
                
                $crossImg = "<img src=" . sbf('img/16/delete.png') . " align=\"absmiddle\" title=\"" . tr("Премахване на файла") . "\" alt=\"\">";
                $html .= "<span class='{$name}_{$fRec->fileHnd} multipleFiles'>" . $this->toVerbal($vId) . "&nbsp;<a class=\"remove-file-link\" href=\"#\" onclick=\"unsetInputFile('" . $name . "', '" . $fRec->fileHnd . "')\">" . $crossImg . '</a></span>';
            }
        }
        
        $tpl = ht::createElement("span", $attrInp, $html, TRUE);
        
        $valueStr = implode(',', $valueFhArr);
        
        $tpl->append("<input name='{$name}' value='{$valueStr}' id='{$name}_id' type='hidden'>");
        
        $bucket = $this->params['bucket'];
        
        $bucketId = fileman_Buckets::fetchByName($bucket);
        
        expect($bucketId, 'Очаква се валидна кофа', $bucket);
        
        $tpl->prepend($Files->makeBtnToAddFile("+", $bucketId, 'placeFile_setInputFile' . $name, array('class' => 'noicon ' . $attrInp['class'] . '_btn', 'title' => 'Добавяне или промяна на файлове')));
        
        $this->addJavascript($tpl, $name);
        
        return $tpl;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function addJavascript($tpl, $name)
    {
        $tpl->appendOnce("
            function placeFile_setInputFile(name, fh, fName) {

                var inputFileHnd = document.getElementById(name + '_id');
                
                if (inputFileHnd.value.search(fh) == -1) {
                    inputFileHnd.value += inputFileHnd.value ? ',' : '';
                    inputFileHnd.value += fh;
                    
                    var divFileName = document.getElementById(name + '_files_name_id');
                    var crossImg = '<img src=" . sbf('img/16/delete.png') . " align=\"absmiddle\" alt=\"\">';
                    divFileName.innerHTML += '<span class=\"' + name + '_' + fh + ' multipleFiles\">' + getDownloadLink(fName, fh) + 
                    '&nbsp;<a class=\"remove-file-link-new\" href=\"#\" onclick=\"unsetInputFile(\'' + name + '\', \'' + fh + '\')\">' + crossImg + '</a></span>';
                }
                
                return true;
            }

            function getDownloadLink(fName, fh) {
                var url = '" . toUrl(array('fileman_Download', 'Download', 'fh' => '1')) . "';
                url = url.replace('1', fh);
                var link = '<a href=\"' + url + '\" target=\"_blank\">' + fName + '</a>';

                return link;
            }
        ", 'SCRIPTS');
        
        $tpl->appendOnce("
            function unsetInputFile(name, fh) {
                var spanFileName = document.getElementsByClassName(name + '_' + fh);
                for(var i=0; i < spanFileName.length; i++) {
                    spanFileName[i].innerHTML = '';
                }
                
                var inputFileHnd = document.getElementById(name + '_id');
                var inputVal = inputFileHnd.value;
                inputVal = inputVal.replace(',' + fh, '');
                inputVal = inputVal.replace(fh + ',', '');
                inputVal = inputVal.replace(fh, '');
                inputFileHnd.value = inputVal;
            }
        ", 'SCRIPTS');
        
        $tpl->appendOnce("
            function placeFile_setInputFile{$name}(fh, fName)
            {
                return placeFile_setInputFile('{$name}', fh, fName);
            }
        ", 'SCRIPTS');
    }
}
