<?php

/**
 * Клас 'doc_DocumentIntf' - Интерфейс за мениджърите на документи
 *
 * @category   Experta Framework
 * @package    doc
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class doc_DocumentIntf
{
    function route($rec)
    {
        $this->class->route($rec);
    }
}