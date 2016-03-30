<?php


class document_OrderDetails extends core_Detail
{
    public $singleTitle = "Продукт";
    public $masterKey = 'orderId';

    public $loadList = "plg_SaveAndNew";

    function description()
    {
        $this->FLD('orderId', 'key(mvc=document_Orders, select = number)', 'caption=poruchka');
        $this->FLD('productId', 'key(mvc=document_Products, select = title)', 'caption=product');
        $this->FLD('quantity', 'double', 'caption=kolichestvo');
    }



}

