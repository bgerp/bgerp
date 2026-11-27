<?php


/**
 * Проверки на подсказките в клетките на табличния изглед.
 *
 * @category  ef
 * @package   core
 *
 * @author    Stefan Arsov <stefan.arsof@gmail.com> with Codex
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class core_tests_TableView extends unit_Class
{
    public static function test_CellHintFields(core_TableView $table)
    {
        $fields = 'code=Код,quantity=Количество,detail=@';
        $text = 'A & "B" <C> [#quantity#]';
        $rows = array(
            (object) array('code' => 'A', 'quantity' => 2, 'name' => $text, 'detail' => 'D', 'detailHint' => 'More'),
            array('code' => 'B', 'quantity' => 3, 'name' => 'Second'),
            array('code' => 'C', 'quantity' => 4),
            array('code' => 'D', 'quantity' => 5, 'name' => ''),
        );

        $table = clone $table;
        $plain = $table->get($rows, $fields)->getContent();
        $table->cellHintFields = array('code' => 'name', 'detail' => 'detailHint');
        $html = $table->get($rows, $fields)->getContent();
        $doc = new DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($doc);

        ut::expectEqual($xpath->evaluate('string(//tbody[1]/tr[1]/td[1]/@title)'), $text);
        ut::expectEqual($xpath->evaluate('string(//tbody[2]/tr[1]/td[1]/@title)'), 'Second');
        ut::expectEqual($xpath->evaluate('string(//tbody[1]/tr[2]/td[@colspan="2"]/@title)'), 'More');
        ut::expectEqual($xpath->query('//td[@title]')->length, 3);
        ut::expectEqual(preg_replace('/ title="[^"]*"/', '', $html), $plain);
    }
}
