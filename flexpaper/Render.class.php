<?php


/**
 * Път до външния файл
 */
defIfNot('FLEXPAPER_PATH', sbf('flexpaper/1.4.5/FlexPaperViewer.swf'));


/**
 * Клас 'flexpaper_Render'
 *
 * Клас, който шаблон за използване на flexpaper.
 * Съдържа необходимите функции за използването на FlexPaper
 *
 *
 * @category  vendors
 * @package   flexpaper
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class flexpaper_Render
{
    /**
     * Генерира флаш за разглеждане на документи
     *
     * @param string $swfHnd                          - FileHandler на swf файла
     * @param string $flexId                          - Уникалното id на div' а, в който се намира swfobj
     * @param array  $params                          - Масив от атрибути, които се използват от flexpaper
     * @param float  $params['Scale']                 - Първоначалното увеличение. Трябва да е над 0 (1=100%)
     * @param string $params['ZoomTransition']        - Начина на преход при увеличаване на файла.
     *                                                Използва същите преходи като при "Tweener". Пример: easenone, easeout, linear, easeoutquad
     * @param float  $params['ZoomTime']              - Времето необходимо за увеличение. Трябва да е над 0.
     * @param float  $params['ZoomInterval']          - Интервалът, който плъзгача за мащабиране ползва.
     *                                                Трябва да е положително число.
     * @param bool   $params['FitPageOnLoad']         - Да пасва на страницата при зареждане
     * @param bool   $params['FitWidthOnLoad']        - Да пасва на широчината при зареждане
     * @param string $params['localeChain']           - Задава език за използване
     * @param bool   $params['FullScreenAsMaxWindow'] - Задава дали да се отвори в нова страница, при увеличаване на цял екран.
     * @param bool   $params['ProgressiveLoading']    - Дали да се зареди документа постепенно или да се изчака цялото му зареждане.
     *                                                Необходимо е флаша да е над версия 9.
     * @param float  $params['MaxZoomSize']           - Задава максималното допустимо ниво на мащабиране.
     * @param float  $params['MinZoomSize']           - Задава минималното допустимо ниво на мащабиране.
     * @param bool   $params['SearchMatchAll']        - Ако TRUE, тогава се подчертават всички съвпадения при търсене на документа.
     * @param string $params['InitViewMode']          - Задава началния изглед. Пример: "Portrait" или "TwoPage".
     * @param bool   $params['ViewModeToolsVisible']  - Показване или скриване режима на преглед от лентата с инструменти.
     * @param bool   $params['ZoomToolsVisible']      - Показване или скриване инструментите за мащабиране от лентата.
     * @param bool   $params['NavToolsVisible']       - Показване или скриване инструментите за навигация от лентата.
     * @param bool   $params['CursorToolsVisible']    - Показване или скриване на инструментите за курсора от лентата.
     * @param bool   $params['SearchToolsVisible']    - Показване или скриване на инструментите за търсене в лентата.
     * @param bool   $params['PrintEnabled']          - Дали да е активирана функцията за разпечатване на документа
     * @param float  $params['width']                 - Широчина на блока
     * @param float  $params['height']                - Височина на блока
     * @param array  $attributes                      - Масив от атрибути, които се използват от swfObj
     * @param string $attributes['id']                - Id, което се използва в swfObj
     * @param string $attributes['name']              - Name, което се използва в swfObj
     * @param array  $paramsSwf                       - Масив от параметри, които се използват от swfObj
     * @param string $paramsSwf['quality']            - Качество, което се използва в swfObj
     * @param string $paramsSwf['bgcolor']            - цвят на background, който се използва в swfObj
     * @param bool   $paramsSwf['allowfullscreen']    - Разрешаване на уголемяването на цял екран в swfObj
     * @param string $paramsSwf['wmode']              - wmode, което се използва в swfObj
     *
     * return $tpl
     */
    public function view($swfHnd, $flexId = null, $params = null, $attributes = null, $paramsSwf = null)
    {
        $Files = cls::get('fileman_Files');
        $fileName = $Files->fetchByFh($swfHnd, 'name');
        $filePath = $Files->fetchByFh($swfHnd, 'path');
        $filePath = escapeshellarg($filePath);
        $isSwf = exec("file --mime-type {$filePath}", $retValue, $isCorrect);
        
        if ($isCorrect) {
            
            return 'Възникна грешка, моля опитайте пак.';
        }
        if (!stristr($isSwf, 'application/x-shockwave-flash')) {
            
            return 'Файлът, който сте избрали не е флаш.';
        }
        
        
        $FilemanDownload = cls::get('fileman_Download');
        $params['SwfFile'] = $FilemanDownload->getDownloadUrl($swfHnd);
        
        setIfNot($params['Scale'], '0.6');
        setIfNot($params['ZoomTransition'], 'easeOut');
        setIfNot($params['ZoomTime'], '0.5');
        setIfNot($params['ZoomInterval'], '0.2');
        setIfNot($params['FitPageOnLoad'], 'false');
        setIfNot($params['FitWidthOnLoad'], 'true');
        setIfNot($params['localeChain'], 'en_US');
        setIfNot($params['FullScreenAsMaxWindow'], 'false');
        setIfNot($params['ProgressiveLoading'], 'true');
        setIfNot($params['MaxZoomSize'], '10');
        setIfNot($params['MinZoomSize'], '0.2');
        setIfNot($params['SearchMatchAll'], 'true');
        setIfNot($params['InitViewMode'], 'Portrait');
        setIfNot($params['ViewModeToolsVisible'], 'true');
        setIfNot($params['ZoomToolsVisible'], 'true');
        setIfNot($params['NavToolsVisible'], 'true');
        setIfNot($params['CursorToolsVisible'], 'true');
        setIfNot($params['SearchToolsVisible'], 'true');
        setIfNot($params['PrintEnabled'], 'true');
        
        $width = setIfNot($params['width'], '100%');
        $height = setIfNot($params['height'], '490');
        unset($params['width']);
        unset($params['height']);
        
        setIfNot($attributes['id'], 'FlexPaperViewer');
        setIfNot($attributes['name'], 'FlexPaperViewer');
        
        setIfNot($paramsSwf['quality'], 'high');
        setIfNot($paramsSwf['bgcolor'], '#ffffff');
        setIfNot($paramsSwf['allowfullscreen'], 'true');
        setIfNot($paramsSwf['wmode'], 'opaque');
        
        $html = new ET("<p> За да използвате услугата трябва да имате инсталиран flash player и JavaScript' а да ви е активиран.</p>
                        <p>    Можете да изтеглите flash player от <a href=\"http://www.adobe.com/go/getflash\" target=\"_blank\">тук</a>. <p>
                        
                    ");
        
        $swfObj = cls::createObject('swf_Object');
        $swfObj->setSwfFile(FLEXPAPER_PATH);
        
        $swfObj->setAlternativeContent($html);
        
        $swfObj->setWidth($width);
        $swfObj->setHeight($height);
        $swfObj->setFlashvars($params);
        $swfObj->setAttributes($attributes);
        $swfObj->setParams($paramsSwf);
        
        return $swfObj->getContent();
    }
}
