<?php


/**
 * Версията на пакете, който използва markdown
 */
//defIfNot('MARKDOWN_VERSION', '1.0.1');
defIfNot('MARKDOWN_VERSION', 'extra-1.2.5');


/**
 * Конвертира markdown текстовете в HTML
 *
 * @category  vendors
 * @package   markdown
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class markdown_Render
{
    /**
     * Конвертира посочени markdown текст в HTML формат
     */
    public static function Convert($html)
    {
        //Пътя до файла на markdown
        $filePath = MARKDOWN_VERSION . '/' . 'markdown.php';
        
        // Очакваме да няма грешка при включване на файла
        expect(@include_once($filePath), "Не може да бъде намерен файла: {$filePath}");
        
        //Конвертираме от markdown към html
        $convertedHtml = Markdown($html);
        
        //Връщаме конвертирания текст
        return $convertedHtml;
    }
}
