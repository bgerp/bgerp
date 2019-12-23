<?php

/**
 * Клас 'page_PureHtml' - Общ шаблон за всички HTML страници
 *
 *
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class page_PureHtml extends core_ET
{
    /**
     * Конструктор, който генерира лейаута на шаблона
     */
    public function __construct()
    {
        $bodyClass = Mode::is('screenMode', 'narrow') ? 'narrow narrow-scroll' : 'wide';

        $bodyId = str::getRand();

        parent::__construct(
            '<!doctype html>' .

            (Mode::is('screenMode', 'narrow') ?
                "\n<html xmlns=\"http://www.w3.org/1999/xhtml\"[#HTML_ATTR#]>" :
                "\n<html[#HTML_ATTR#]>") .

            "\n<head>" .
            "\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1, maximum-scale=2\">" .
            "\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=[#ENCODING#]\">" .
            "<!--ET_BEGIN META_DESCRIPTION-->\n<meta name=\"description\" content=\"[#META_DESCRIPTION#]\"><!--ET_END META_DESCRIPTION-->" .
            "<!--ET_BEGIN META_KEYWORDS-->\n<meta name=\"keywords\" content=\"[#META_KEYWORDS#]\"><!--ET_END META_KEYWORDS-->" .
            "<!--ET_BEGIN PAGE_TITLE-->\n<title>[#NOT_CNT#][#PAGE_TITLE#]</title><!--ET_END PAGE_TITLE-->" .
            "<!--ET_BEGIN STYLE_IMPORT-->\n<style type=\"text/css\">[#STYLE_IMPORT#]\n</style><!--ET_END STYLE_IMPORT-->" .
            "<!--ET_BEGIN STYLES-->\n<style type=\"text/css\">[#STYLES#]\n</style><!--ET_END STYLES-->" .
            '<!--ET_BEGIN HEAD-->[#HEAD#]<!--ET_END HEAD-->' .
            "\n</head>" .
            "\n<body<!--ET_BEGIN ON_LOAD--> onload=\"[#ON_LOAD#]\"<!--ET_END ON_LOAD--> id= \"{$bodyId}\" class=\"{$bodyClass} [#BODY_CLASS_NAME#]\">" .
            '<!--ET_BEGIN PAGE_CONTENT-->[#PAGE_CONTENT#]<!--ET_END PAGE_CONTENT-->' .
            "<script type=\"text/javascript\">[#SCRIPTS#][#JQRUN#]\n</script>" .
            "\n</body>" .
            "\n</html>"
        );
    }

    /**
     * Интерфейсен метод
     *
     * @see core_page_WrapperIntf
     */
    public function prepare()
    {
    }


    /**
     * Прихваща събитието 'output' на ЕТ, за да добави стиловете и javascripts
     */
    public static function on_Output(&$invoker)
    {
        // Добавяне на хедърите
        $headers = $invoker->getArray('HTTP_HEADER');
        if (!empty($headers)) {
            foreach ($headers as $hdr) {
                if ($hdr{0} == '-') {
                    header_remove(substr($hdr, 1));
                } else {
                    header($hdr, true);
                }
            }
        }

        // Добавяне на файловете
        $files = (object) array(
            'css' => $invoker->getArray('CSS'),
            'js' => $invoker->getArray('JS'),
            'invoker' => $invoker
        );

        $invoker->appendFiles($files);
    }


    /**
     * Ако няма кой да прихване извикването на функцията
     *
     * @param array $css
     */
    public function appendFiles_($files)
    {
        // Дали връзките да са абсолютни
        $absolute = (boolean) (Mode::is('text', 'xhtml'));

        if (is_array($files->css)) {
            foreach ($files->css as $file) {
                $file = $this->getFileForAppend($file, $absolute);

                $files->invoker->appendOnce("\n@import url(\"{$file}\");", 'STYLE_IMPORT', true);
            }
        }

        if (is_array($files->js)) {
            foreach ($files->js as $file) {
                $file = $this->getFileForAppend($file, $absolute);
                if($this instanceof cms_page_External) {
                    $attr = "defer";
                }
                $files->invoker->appendOnce("\n<script {$attr} type=\"text/javascript\" src=\"{$file}\"></script>", 'HEAD', true);
            }
        }
    }


    /**
     * Връща файла за добавя. Ако е от системата минава през sbf.
     * Ако е външен линк, не го променя
     *
     * @param string    $filePath
     * @param NULL|bool $absolute
     *
     * @return string
     */
    public static function getFileForAppend($filePath, $absolute = null)
    {
        if (preg_match('#^[^/]*//#', $filePath)) {

            return $filePath;
        }

        if (!isset($absolute)) {
            $absolute = (boolean) (Mode::is('text', 'xhtml') || Mode::is('printing') || Mode::is('pdf'));
        }

        $filePath = sbf($filePath, '', $absolute);

        return $filePath;
    }



}
