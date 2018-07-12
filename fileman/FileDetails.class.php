<?php


/**
 * Детайл на файловете.
 *
 * Държи всички версии на файлове, които са създадени от даден файл
 *
 * @category  vendors
 * @package   fileman
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class fileman_FileDetails extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Версии на файловете';
    
    
    public $canAdd = 'no_one';
    
    
    public $canEdit = 'no_one';
    
    
    public $canDelete = 'no_one';
    
    
    public $canSingle = 'no_one';
    
    
    public $canList = 'no_one';
    
    
    public $canView = 'no_one';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'fileId';

//    /**
//     * Плъгини за зареждане
//     */
//    var $loadList = 'plg_Created, plg_RowTools, acc_Wrapper, plg_RowNumbering, plg_AlignDecimals,
//        Accounts=acc_Accounts, Lists=acc_Lists, Items=acc_Items, plg_AlignDecimals, plg_SaveAndNew';
    public $loadList = 'plg_Created';

//    /**
//     * Полета, които ще се показват в листов изглед
//     */
//    var $listFields = 'tools=Пулт, debitAccId, debitQuantity=Дебит->К-во, debitPrice=Дебит->Цена, creditAccId, creditQuantity=Кредит->К-во, creditPrice=Кредит->Цена, amount=Сума';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('fileId', 'key(mvc=fileman_Files, select=name)', 'column=none,input=hidden,silent');
        $this->FLD('firstFileVersionId', 'key(mvc=fileman_Files, select=name)', 'column=none,input=hidden,silent');
        $this->FLD('versionInfo', 'varchar(64)', 'column=none,input=hidden,silent');
        
        $this->setDbUnique('fileId, firstFileVersionId');
    }
    
    
    /**
     * Връща масив с всички версии на файла.
     *
     * @param numeric $fileId - id' то на файла, за който ще се търсят версиите
     *
     * @return array $fileVersionsArr - Масив с всички версии на съответния файл
     */
    public static function getFileVersionsArr($fileId)
    {
        // Масив с всички версии на файла
        $fileVersionsArr = array();
        
        $query = self::getQuery();
        
        // Ако файла е версия на някой файл
        if ($cRec = self::fetch("#fileId = '{$fileId}'")) {
            
            // Всички записи, които са версии на същия файл ($cRec->firstFileVersionId) и не са текущия файл ($fileId)
            $query->where("#firstFileVersionId = '{$cRec->firstFileVersionId}' AND #fileId != '{$fileId}'");
        } else {
            
            // Всички записи, които са версии на файла
            $query->where("#firstFileVersionId = '{$fileId}'");
        }
        
        // Да са подредени по дадата на създаване
        $query->orderBy('createdOn', 'DESC');
        
        // Обикаляме всички версии на файла
        while ($rec = $query->fetch()) {
            
            // Манипулатора на файла
            $fh = fileman_Files::fetchField($rec->fileId, 'fileHnd');
            
            // Информация за версията
            $fileVersionsArr[$fh]['versionInfo'] = self::getVerbal($rec, 'versionInfo');
            
            // Вербалното име на файла
            $fileVersionsArr[$fh]['fileName'] = fileman_Files::getVerbal($rec->fileId, 'name');
        }
        
        // Ако текущия файл е версия на друг
        if ($cRec) {
            
            // Манипулатора на файла
            $fh = fileman_Files::fetchField($cRec->firstFileVersionId, 'fileHnd');
            
            // Информация за файла
            $fileVersionsArr[$fh]['versionInfo'] = 'Оригинален файл';
            
            // Вербалното име на файла
            $fileVersionsArr[$fh]['fileName'] = fileman_Files::getVerbal($cRec->firstFileVersionId, 'name');
        }
        
        return $fileVersionsArr;
    }
}
