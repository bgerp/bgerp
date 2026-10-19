<?php


/**
 * Unit тестове за plg_HighlightListSearch
 *
 *
 * @category  ef
 * @package   plg
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class plg_tests_HighlightListSearch extends unit_Class
{
    /**
     * Текстът се оцветява, а масив (напр. search[]=...) се пропуска без грешка
     */
    public static function test_AfterRenderList(plg_HighlightListSearch $Plugin)
    {
        $mvc = (object) array('className' => 'testHighlightList');

        ut::expectEqual(strpos(self::render($Plugin, $mvc, 'test'), '.highlight("test")') !== false, true);
        ut::expectEqual(strpos(self::render($Plugin, $mvc, array('test')), 'highlight') === false, true);
        ut::expectEqual(strpos(self::render($Plugin, $mvc, array('a' => 'x')), 'highlight') === false, true);
    }


    /**
     * Рендира празен списък с подадената стойност за търсене в заявката
     */
    private static function render(plg_HighlightListSearch $Plugin, $mvc, $search)
    {
        $res = new ET('[#JQRUN#]');

        $requestName = 'plgTestsHighlightListSearch';
        Request::push(array('search' => $search), $requestName);

        try {
            $Plugin->on_AfterRenderList($mvc, $res, new stdClass());
        } finally {
            Request::pop($requestName);
        }

        return $res->getContent();
    }
}
