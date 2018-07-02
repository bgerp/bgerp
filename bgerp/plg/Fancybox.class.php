<?php


/**
 * Създава линк към свалянето на картинката в plain режим
 *
 * @category  bgerp
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_plg_Fancybox extends core_Plugin
{
    /**
     * Създава линк към свалянето на картинката в plain режим
     *
     * @param core_Mvc $mvc
     * @param core_Et  $resTpl
     * @param string   $fh
     * @param int      $thumbSize
     * @param int      $maxSize
     * @param string   $baseName
     * @param array    $imgAttr
     * @param array    $aAttr
     */
    public function on_BeforeGetImage($mvc, &$resTpl, $fh, $thumbSize, $maxSize, $baseName = null, $imgAttr = array(), $aAttr = array())
    {
        // Да сработва само за plain режим
        if (!Mode::is('text', 'plain')) {
            return;
        }

        // Създава линк към свалянето на картинката
        $resUrl = toUrl(array('F', 'T', doc_DocumentPlg::getMidPlace(), 'n' => $baseName), $imgAttr['isAbsolute'], true, array('n'));
        $resTpl = new ET(tr('Картинка|*: ').$resUrl);

        return false;
    }
}
