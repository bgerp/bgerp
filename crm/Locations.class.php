<?php

/**
 * Локации
 */
class crm_Locations extends core_Manager {
    
    /**
     * Интерфайси, поддържани от този мениджър
     */
    // var $interfaces = 'acc_RegisterIntf';

    /**
     *  @todo Чака за документация...
     */
    var $title = "Локации";
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, crm_Wrapper, plg_Rejected';
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = "id, contragent=Контрагент, title, typeId, countryId, city, pCode, address, comment, gln";
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {   
        $this->FLD('contragentCls', 'class(interface=crm_ContragentAccRegIntf)', 'caption=Собственик->Клас,input=hidden,silent');
        $this->FLD('contragentId', 'int', 'caption=Собственик->Id,input=hidden,silent');
        $this->FLD('title', 'varchar(255)', 'caption=Наименование,mandatory');
        $this->FLD('countryId', 'key(mvc=drdata_Countries, select=commonName, allowEmpty)', 'caption=Юрисдикция');
        $this->FLD('city', 'varchar(64)', 'caption=Град');
        $this->FLD('pCode', 'varchar(16)', 'caption=П. код');
        $this->FLD('address', 'varchar(255)', 'caption=Адрес');
        $this->FLD('comment', 'text', 'caption=Коментари');
        $this->FLD('gln', 'gs1_TypeEan13', 'caption=GLN код');
        $this->FLD('gpsCoords', 'location_Type', 'caption=Координати');
    }



    /**
     *
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $cMvc = cls::get($rec->contragentCls);
        $row->contragent = $cMvc->getTitleById($rec->contragentId);

        if($mvc->haveRightFor('single', $rec->contragentId)) {
            $row->contragent = ht::createLink($row->contragent, array($cMvc, 'single', $rec->contragentId, 'ret_url' => TRUE));
        }

    }


    /**
     *
     */
    function prepareLocationsDetails($data)
    {
        expect($data->contragentCls = core_Classes::fetchIdByName($data->masterMvc));
        expect($data->masterId);
        $query = $this->getQuery();
        $query->where("#contragentCls = {$data->contragentCls} AND #contragentId = {$data->masterId}");
        while($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $row = $data->rows[$rec->id] = $this->recToVerbal($rec);
        }
    }

    /**
     * Рендира данните
     */
    function renderLocationsDetails($data)
    {
        
        if(count($data->rows)) {
            $tpl = new ET("<fieldset class='detail-info'>
                            <legend class='groupTitle'>" . tr('Локации') . " [#plus#]</legend>
                                <div class='groupList,clearfix21'>
                                 [#locations#]
                                </div>
                        </fieldset>");

            foreach($data->rows as $id => $row) {
                $tpl->append("<div style='padding:3px;'>", 'locations');

                $tpl->append("{$row->title}", 'locations');
                
                if($this->haveRightFor('edit', $id)) {
                    // Добавяне на линк за редактиране
                    $tpl->append("<span style='margin-left:5px;'>", 'locations');
                    $url = array($this, 'edit', $id, 'ret_url' => TRUE);
                    $img = "<img src=" . sbf('img/16/edit-icon.png') . " width='16' valign=bottom  height='16'>";
                    $tpl->append(ht::createLink($img, $url, FALSE, 'title=' . tr('Редактиране на локация')), 'locations');
                    $tpl->append('</span>', 'accounts');
                }
                
                if($this->haveRightFor('delete', $id)) {
                    // Добавяне на линк за изтриване
                    $tpl->append("<span style='margin-left:5px;'>", 'locations');
                    $url = array($this, 'delete', $id, 'ret_url' => TRUE);
                    $img = "<img src=" . sbf('img/16/delete-icon.png') . " width='16' valign=bottom  height='16'>";
                    $tpl->append(ht::createLink($img, $url, 'Наистина ли желаете да изтриете локацията?', 'title=' . tr('Изтриване на локация')), 'locations');
                    $tpl->append('</span>', 'locations');
                }

                $tpl->append("</div>", 'locations');

            }
        } else {
            $tpl = new ET("<fieldset class='detail-info' style='border:none;'>
                            <legend class='groupTitle'>" . tr('Локации') . " [#plus#]</legend>
                                
                           </fieldset>");

        }
        
        $url = array($this, 'add', 'contragentCls' => $data->contragentCls, 'contragentId' => $data->masterId, 'ret_url' => TRUE);
        $img = "<img src=" . sbf('img/16/add.png') . " width='16' valign=bottom  height='16'>";
        $tpl->append(ht::createLink($img, $url, FALSE, 'title=' . tr('Добавяне на нова локация')), 'plus');

        return $tpl;
    }


 }