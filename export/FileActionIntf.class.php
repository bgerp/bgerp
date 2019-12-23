<?php


/**
 * Интерфейс за обработка на експортираните файлове
 *
 * @category  bgerp
 * @package   export
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за типове за експортиране на документи
 */
class export_FileActionIntf
{
    /**
     * Клас имплементиращ мениджъра
     */
    public $class;
    
    
    /**
     * Добавя бутон за обработка на файла
     *
     * @param core_Form $form
     */
    public function addActionBtn($form, $fileHnd)
    {
        return $this->class->addActionBtn($form, $fileHnd);
    }
}
