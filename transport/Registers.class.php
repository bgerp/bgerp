<?php



/**
 * Мениджър на регистър на транспортните документи CMR, B/L
 *
 *
 * @category  bgerp
 * @package   transport
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     регистър на транспортните документи CMR, B/L
 */
class transport_Registers extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Регистър на транспортните документи CMR, B/L';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_SaveAndNew, 
                    transport_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,transport';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,transport';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,transport';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,transport';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,transport';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('numberRequest','key(mvc=transport_Requests, select=number)', 'caption=Номер на заявката');
    	$this->FLD('number','varchar', 'caption=Номер на документа');
    	$this->FLD('type','enum(invoice=Търговска фактура,
    							proform invoice=Проформа фактура,
    							packingList=Опаковъчен лист,
    							formA=Сертификат за произход,
    							EUR1=EUR сертификат/ формуляр,
    							declaration=Декларация върху фактурата,
    							import=Вносни разрешителни,
    							health=Фитосанитарен, Ветеринарен, Здравен Сертификат,
    							quality=Сертификат за качество,
    							insurance=Застрахователна полица,
    							dangerous=Разрешително за опасен товар,
    							ADR=ADR документи,
    							CMR=CMR товарителница,
    							TIR=Карнет  TIR,
    							ATA=Карнет ATA,
    							CPD=Карнет CPD,
    							importExport=Удостоверение за износ/внос,
    							agreement=Декларация за съответствие,
    							weight=Сертификат за тегло)', 'caption=Тип на документа');
    	$this->FLD('date','datetime', 'caption=Дата');
    	$this->FLD('fitNoteFile', 'fileman_FileType(bucket=Transport)', 'caption=Файл');
    	$this->FLD('description','richtext(bucket=Transport)', 'caption=Описание');
    }
    
   
}