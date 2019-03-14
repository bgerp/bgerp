<?php



/**
 * Четене и записване на локални файлове
 *
 *
 * @category  bgerp
 * @package   H18
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Локален файлов архив
 */
class h18_CatProducts extends core_Manager
{
    public $loadList = 'h18_Wrapper';
    /**
     * Заглавие
     */
    public $title = 'Точки на продажби';
    
    function description()
    {
        $conf = core_Packs::getConfig('h18');

        $this->db = cls::get('core_Db',
            array(  'dbName' => $conf->H18_BGERP_DATABASE,
                'dbUser' => $conf->H18_BGERP_USER,
                'dbPass' => $conf->H18_BGERP_PASS,
                'dbHost' => $conf->H18_BGERP_HOST
            ));
        
        $this->dbTableName = 'cat_products';
        
        $this->FLD('proto', "key(mvc=cat_Products,allowEmpty,select=name)", "caption=Шаблон,input=hidden,silent,refreshForm,placeholder=Популярни продукти,groupByDiv=»");
        
        $this->FLD('code', 'varchar(32)', 'caption=Код,remember=info,width=15em');
        $this->FLD('name', 'varchar', 'caption=Наименование,remember=info,width=100%');
        $this->FLD('info', 'richtext(rows=4, bucket=Notes)', 'caption=Описание');
        $this->FLD('measureId', 'key(mvc=cat_UoM, select=name,allowEmpty)', 'caption=Мярка,mandatory,remember,notSorting,smartCenter');
        $this->FLD('photo', 'fileman_FileType(bucket=pictures)', 'caption=Илюстрация,input=none');
        $this->FLD('groups', 'keylist(mvc=cat_Groups, select=name, makeLinks)', 'caption=Групи,maxColumns=2,remember');
        $this->FLD('isPublic', 'enum(no=Частен,yes=Публичен)', 'input=none');
        $this->FNC('quantity', 'double(decimals=2)', 'input=none,caption=Наличност,smartCenter');
        $this->FNC('price', 'double(minDecimals=2,maxDecimals=6)', 'input=none,caption=Цена,smartCenter');
        
        $this->FLD('canSell', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('canBuy', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('canStore', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('canConvert', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('fixedAsset', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('canManifacture', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('meta', 'set(canSell=Продаваем,canBuy=Купуваем,canStore=Складируем,canConvert=Вложим,fixedAsset=Дълготраен актив,canManifacture=Производим)', 'caption=Свойства->Списък,columns=2,mandatory');
    }
    
}