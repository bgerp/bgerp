<?php


/**
 * Клас 'gdocs_Plugin'
 *
 * Плъгин за добавяне на бутона за преглед на документи в google docs
 * Разширения: doc,docx,xls,xlsx,ppt,pptx,pdf,pages,ai,tiff,dxf,svg,eps,ps,ttf,xps,zip,rar
 *
 * @category  vendors
 * @package   gdocs
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class gdocs_Plugin extends core_Plugin
{
    
    
    /**
     * Добавя бутон за разглеждане на документи
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, &$data)
    {
        if ($mvc->haveRightFor('single', $data->rec)) {
            try {
                $rec = $data->rec;
                
                //Разширението на файла
                $ext = fileman_Files::getExt($rec->name);
            
                if(in_array($ext,  arr::make('doc,docx,xls,xlsx,ppt,pptx,pdf,pages,ai,tiff,dxf,svg,eps,ps,ttf,xps,zip,rar'))) { 
                    $url = "//docs.google.com/viewer?url=" . fileman_Download::getDownloadUrl($rec->fileHnd, 1); 
                     
                    // Добавяме бутона
                    $data->toolbar->addBtn('gDocs', $url, 
                        "id='btn-gdocs',ef_icon=gdocs/img/google.png", 
                        array('target'=>'_blank', 'order'=>'30')
                    ); 
                }
            } catch (core_Exception_Expect $expect) {}
        }
    }
    
    
    /**
     * Ембедва URL-то от параметрите в iframe
     * 
     * @param array $params
     * 
     * @return mixed
     */
    static function getOembedRes($params)
    {
        $url = $params['url'];
        
        // Ако е презентация, трябва да се промени линка
        if (strpos($url, '/presentation/')) {
            $url = str_replace('/pub', '/embed', $url);
        } elseif (strpos($url, '/file/')) {
            
            $urlArr = parse_url($url);
            
            $urlPathArr = explode('/', $urlArr['path']);
            
            $lastElementOfArray = array_slice($urlPathArr, -1, 1, TRUE);
            
            $lastKey = key($lastElementOfArray);
            
            if (($lastKey == 4) && (
                    ($urlPathArr[$lastKey] == 'preview') || 
                    ($urlPathArr[$lastKey] == 'edit') || 
                    ($urlPathArr[$lastKey] == 'view') || 
                    ($urlPathArr[$lastKey] == 'share'))
                ) {
                $urlPathArr[$lastKey] = 'preview';
            } else {
                $urlPathArr[] = 'preview';
            }
            
            $urlArr['path'] = implode('/', $urlPathArr);
            
            $url = $urlArr['scheme'];
            if ($url) {
                $url .= '://';
            }
            
            $url .= $urlArr['host'];
            $url .= $urlArr['path'];
        } else {
            
            // Добавяме необходимите параметри
            $url = core_Url::addParams($url, array('widget' => 'true', 'embedded' => 'true'));
        }
        
        $conf = core_Packs::getConfig('gdocs');
        
        setIfNot($width, $params['width'], $conf->GDOCS_DEFAULT_WIDTH);
        setIfNot($height, $params['height'], $conf->GDOCS_DEFAULT_HEIGHT);
        
        // Резултатния HTML
        $res['html'] = "<iframe src='{$url}' frameborder='0' width='{$width}' height='{$height}' allowfullscreen='true' mozallowfullscreen='true' webkitallowfullscreen='true'></iframe>";
        
        // Колко време да се кешира
        $res['cache_age'] = $params['cache_age'];
        
        // Ако трябва да се връща като JSON
        if ($params['format'] == 'json') {
            
            $res = json_encode($res);
        }
        
        return $res;
    }
}
