<?php 

/**
 * Дълготрайни активи
 */
class dma_Inventory extends core_Master
{
    /**
     * Интерфайси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf,dma_DaAccRegIntf';


    /**
     *  @todo Чака за документация...
     */
    var $title = "Дълготрайни активи";
    
        
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, dma_Wrapper, plg_State2, plg_Printing,
                     acc_plg_Registry, plg_Sorting, plg_SaveAndNew';
    
    
    /**
     * Права
     */
    var $canRead = 'admin,dma';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canWrite = 'admin,dma';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('num', 'int', 'caption=Наш номер, mandatory');
        $this->FLD('serial', 'varchar', 'caption=Сериен номер');
        
        $this->FLD('title', 'varchar', 'caption=Заглавие,width=400px');
        
        $this->FLD('info', 'text', 'caption=Описание,column=none,width=400px');
        
        $this->FLD('origin', 'text', 'caption=Произход,column=none,width=400px');
        
        $this->FLD('inUseSince', 'date', 'caption=В употреба от');
        
        $this->FLD('amortNorm', 'double', 'caption=ГАН,hint=Годишна амортизационна норма,unit=%');
        
        $this->setDbUnique('num');
    }
    
    
    /**
     * Връща заглавието и мярката на перото за продукта
     *
     * Част от интерфейса: intf_Register
     */
    function getAccItemRec($rec)
    {
        return (object) array( 'title' => $rec->title,
            'num' => $rec->num
        );
    }
}