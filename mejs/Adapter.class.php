<?php


/**
 * Създава плейър за изпълнение на видео и аудио
 *
 * @category  vendors
 * @package   mejs
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class mejs_Adapter
{
    
    
    /**
     * Създава плейър за съответното видео
     *
     * @param mixed  $source   - Стринг или масив от файлове за плейване
     * @param array  $params   - Параметри
     * @param string $fileType - Типа на сорса - handler, url, path
     *
     * @return core_Et $mTpl - Шаблон, който трябва да се добави към родителския шаблон
     */
    public static function createVideo($source, $params = null, $fileType = 'handler')
    {
        return static::create($source, 'video', $params, $fileType);
    }
    
    
    /**
     * Създава плейър за съответното аудио
     *
     * @param mixed  $source   - Стринг или масив от файлове за плейване
     * @param array  $params   - Параметри
     * @param string $fileType - Типа на сорса - handler, url, path
     *
     * @return core_Et $mTpl - Шаблон, който трябва да се добави към родителския шаблон
     */
    public static function createAudio($source, $params = null, $fileType = 'handler')
    {
        return static::create($source, 'audio', $params, $fileType);
    }
    
    
    /**
     * Създава шаблон за плейване на съответното audio или video
     *
     * @param mixed  $source   - Стринг или масив от файлове за плейване
     * @param string $type     - Типа на документа - video или audio
     * @param array  $params   - Параметри
     * @param string $fileType - Типа на сорса - handler, url, path
     *
     * @return core_Et $mTpl - Шаблон, който трябва да се добави към родителския шаблон
     */
    public static function create($source, $type = 'video', $params = null, $fileType = 'handler')
    {
        // Очакваме типа да е или аудио или видео
        expect(($type == 'video' || $type == 'audio'));
        
        // Очакваме типа на файла да е в позволените
        expect(in_array($fileType, array('url', 'path', 'handler')));
        
        // Ако не е масив
        if (!is_array($source)) {
            $sourceArr = array();
            
            // Добавяме стринга в масив
            $sourceArr[$source] = $source;
            
            // Заместваме стринга с масива
            $source = $sourceArr;
        }
        
        // Генерираме уникално id
        $id = core_Os::getUniqId('mejs');
        
        // Шаблона, който ще връщаме
        $mTpl = new ET("<[#TYPE#] <!--ET_BEGIN WIDTH-->width='[#WIDTH#]'<!--ET_END WIDTH-->
                        	<!--ET_BEGIN HEIGHT-->height='[#HEIGHT#]'<!--ET_END HEIGHT--> 
                			id='{$id}'>
                			[#SOURCE#]
            			</[#TYPE#]>
            			");
        
        // Обхождаме всички линкове
        foreach ($source as $src) {
            
            // Вземаме URL' то за сваляне
            $src = fileman_Download::getDownloadUrl($src, 1, $fileType);

            // Създваме шаблон за плейване на видеото
            $tpl = "<source src='{$src}'> \n";
            
            // Добавяме в шаблона
            $mTpl->append($tpl, 'SOURCE');
        }

        // Заместваме плейсхолдерите
        $mTpl->replace($params['width'], 'WIDTH');
        $mTpl->replace($params['height'], 'HEIGHT');
        $mTpl->replace($type, 'TYPE');
        
        // Премахваме празните плейсхолдери
        $mTpl->removeBlocks();

        // Добавяме файловете за плейване с mediaElement
        static::enableMeJs($mTpl, $type, $params);

        return $mTpl;
    }
    
    
    /**
     * Добавя файловете и скрипта необходими за стартиране на mediaElement
     *
     * @param core_Et $tpl
     */
    public static function enableMeJs($tpl, $type = 'video', $params = null)
    {
        // Превръщаме параметрите в стринг
        $paramsStr = static::prepareParams($params);
        
        // Добавяме скрипта за стартиране
        $tpl->append("<script> $('{$type}').mediaelementplayer({$paramsStr}); </script>");
        
        // Добавяме CSS
        $tpl->push('mejs/' . mejs_Setup::get('VERSION') . '/build/mediaelementplayer.css', 'CSS');
        
        // Добавяме JS
        $tpl->push('mejs/' . mejs_Setup::get('VERSION') . '/build/mediaelement-and-player.js', 'JS');
    }
    
    
    /**
     * Преобразува масива с параметри в JSON вид
     *
     * @param array $params - Масив с параметри
     */
    public static function prepareParams($params = null)
    {
        // Ако не са зададени, какви да са по подразбиране някои параметри
        setIfNot($params['loop'], false); // Ауто реплай
        setIfNot($params['pauseOtherPlayers'], true); // При пускане на единия, да спира другите
        setIfNot($params['startVolume'], 1); // 0..1 - Сила на звука
        setIfNot($params['features'], array('playpause','loop','progress','current','duration','tracks','volume','fullscreen'));
        
        // Връщаме в JSON вид
        return json_encode($params);
    }
}
