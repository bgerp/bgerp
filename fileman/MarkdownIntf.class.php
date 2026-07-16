<?php


/**
 * Клас 'fileman_MarkdownIntf' - Интерфейс за извличане на съдържанието на файл в markdown
 *
 * @category  vendors
 * @package   fileman
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class fileman_MarkdownIntf
{
    /**
     * Класът, който имплементира интерфейса
     */
    public $class;


    /**
     * Проверка дали може да се извлича markdown от файла
     *
     * @param stdClass|string $fRec
     */
    public function canExtract($fRec)
    {
        return $this->class->canExtract($fRec);
    }


    /**
     * Извличане на съдържанието на файла в markdown
     *
     * Резултатът се записва във fileman_Indexes с тип 'markdown'
     *
     * @param stdClass|string $fRec
     */
    public function getMarkdown($fRec)
    {
        return $this->class->getMarkdown($fRec);
    }
}
