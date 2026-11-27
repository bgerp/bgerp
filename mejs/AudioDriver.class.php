<?php


/**
 * Клас 'mejs_AudioDriver'
 *
 * Вграждане на видео и аудио обекти в bgERP
 *
 * @category  bgerp
 * @package   mejs
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 */
class mejs_AudioDriver extends mejs_MediaDriver
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'cms_LibraryIntf';

    protected const FORMATS = array(
        'mp3' => array('mp3', array(), array('mp3')),
        'ogg' => array('ogg', array(), array('vorbis', 'opus')),
        'wav' => array('wav', array(), array('pcm_s16le', 'pcm_u8', 'pcm_s24le', 'pcm_f32le')),
    );

    protected const SCOPED_FIELDS = 'source1,title';
    
    
    /**
     * Заглавие на класа
     */
    public $title = 'Аудио';
    
    
    /**
     * Допълва дадената форма с параметрите на фигурата
     * Връща масив от имената на параметрите
     */
    public static function addFields(&$form)
    {
        $form->FLD('source1', 'fileman_FileType(bucket=cms_Audio,align=vertical)', 'caption=Файл|* 1');
        $form->FLD('source2', 'fileman_FileType(bucket=cms_Audio,align=vertical)', 'caption=Файл|* 2');
        $form->FLD('title', 'varchar', 'caption=Заглавие');
    }
    
    
    /**
     * Връща HTML представянето на обекта
     *
     * @param stdClass $rec Записа за елемента от модела-библиотека
     * @param $maxWidth int Максимална широчина на елемента
     * @param $isAbsolute bool Дали URL-тата да са абсолютни
     *
     * @return core_ET|string Представяне на обекта в HTML шабло
     */
    public static function render($rec, $maxwidth = 1200, $absolute = false)
    {
        // Ако е текстов режим, да не сработва
        if (Mode::is('text', 'plain')) {
            
            return '';
        }
        
        // Определяме широчината на видеото в зависимост от мода
        $width = '100%';
        $height = '100%';
                
        $scoped = cms_Library::hasAccessContext($rec);
        if ($scoped) {
            $source = self::getStreamSources($rec, $absolute);
            if (!$source) return '';
            $title = htmlspecialchars($rec->title ?? '', ENT_QUOTES, 'UTF-8');
        } else {
            $source = array();
            if (!empty($rec->source1)) {
                $source[$rec->source1] = $rec->source1;
            }
            if (!empty($rec->source2)) {
                $source[$rec->source2] = $rec->source2;
            }
            $title = $rec->title ?? null;
        }
 
        // Шаблона за видеото
        $tpl = mejs_Adapter::createAudio($source, array('width' => $width, 'height' => $height, 'title' => $title), $scoped ? 'stream' : 'handler');

        
        return $tpl;
    }
}
