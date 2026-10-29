<?php


/**
 * Default за показване на лиценза преди настройването на базата
 */
defIfNot('MARKDOWN_VERSION', 'extra-1.2.5');


/**
 * Конвертира markdown текстовете в HTML
 *
 * @category  vendors
 * @package   markdown
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class markdown_Render
{
    /**
     * Конвертира посочени markdown текст в HTML формат
     *
     * @param string|null $html
     * @param bool $useDefault Без четене на core_Packs при първоначална инсталация
     * @return string
     */
    public static function Convert($html, $useDefault = false)
    {
        $version = $useDefault ? MARKDOWN_VERSION : markdown_Setup::get('VERSION');
        expect(in_array($version, array('1.0.1', 'extra-1.2.5', '2.0.0'), true), 'Неподдържана Markdown версия', $version);
        $html = (string) ($html ?? '');

        if ($version === '2.0.0') {
            expect(PHP_VERSION_ID >= 70400, 'PHP Markdown 2.0.0 изисква PHP 7.4 или по-нова версия');
            require_once __DIR__ . '/2.0.0/Michelf/MarkdownExtra.inc.php';

            return \Michelf\MarkdownExtra::defaultTransform($html);
        }

        require_once __DIR__ . '/' . $version . '/markdown.php';

        return Markdown($html);
    }
}
