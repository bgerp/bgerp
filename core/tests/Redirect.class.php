<?php


/**
 * Ръчна регресионна проверка на редиректи без бизнес записи.
 *
 * @category   bgerp
 * @package    core
 * @author     Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright  2026 Experta OOD
 * @license    GPL 3
 * @since      2026-10-02
 */
class core_tests_Redirect extends core_BaseClass
{
    /**
     * Показва връзките за ръчната HTTP/AJAX регресия.
     *
     * @return core_ET
     */
    public function act_Default()
    {
        requireRole('debug');
        $tpl = new ET('<h2>Redirect regression</h2>');
        foreach (array('Direct', 'Template', 'HtmlStatus') as $action) {
            $tpl->append(ht::createLink($action, array($this, $action)) . '<br>');
        }

        foreach (array('Ajax' => 'Ajax', 'AjaxTemplate' => 'Template') as $title => $action) {
            $url = array($this, $action);
            $onclick = 'getEfae().process({redirectTest: ' . json_encode(toUrl($url, 'local')) . '}); return false;';
            $tpl->append(ht::createLink($title, $url, false, array('onclick' => $onclick)) . '<br>');
        }

        return $tpl;
    }


    /**
     * Проверява директен redirect() през външния обработчик в boot.
     *
     * @throws core_exception_Redirect
     */
    public function act_Direct()
    {
        requireRole('debug');
        redirect(array($this, 'Result', 'source' => 'direct'), false, 'Redirect regression: direct');
    }


    /**
     * Проверява запазения договор за return new Redirect().
     *
     * @return core_Redirect
     */
    public function act_Template()
    {
        requireRole('debug');

        return new Redirect(array($this, 'Result', 'source' => 'template'), 'Redirect regression: template');
    }


    /**
     * Проверява запазването на HTML в статус съобщението.
     *
     * @throws core_exception_Redirect
     */
    public function act_HtmlStatus()
    {
        requireRole('debug');
        redirect(array($this, 'Result', 'source' => 'html'), false, 'Създаден <b>документ</b>');
    }


    /**
     * Проверява фиксирането на AJAX режима преди възстановяването на Request.
     *
     * @throws core_exception_Redirect
     */
    public function act_Ajax()
    {
        requireRole('debug');
        Request::push(array('ajax_mode' => true), 'redirectTest');
        try {
            redirect(array($this, 'Result', 'source' => 'ajax'), false, 'Redirect regression: AJAX');
        } finally {
            Request::pop('redirectTest');
        }
    }


    /**
     * Крайна страница, която потвърждава достигането на дестинацията.
     *
     * @return core_ET
     */
    public function act_Result()
    {
        requireRole('debug');
        $source = Request::get('source', 'varchar');
        expect(in_array($source, array('direct', 'template', 'ajax', 'html'), true));

        return new ET('<h2>PASS: ' . $source . ' redirect reached its destination</h2>');
    }
}
