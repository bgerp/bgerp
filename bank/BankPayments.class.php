<?php

/**
 * Метод API: обхожда всички "активни" и "в заявка" приходни и разходни
 *  банкови документи за посочен период
 *
 * @category  bgerp
 * @package   bank
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Банка » Платежни банкови документи
 */
class bank_BankPayments extends core_Manager
{
    public $title = "Платежни банкови документи";


    public $loadList = 'plg_RowTools2,plg_Sorting';

    public $listFields ='from,to';

    protected function description()
    {

        $this->FLD('from', 'date(smartTime)', 'caption=От,after=title,single=none,mandatory');
        $this->FLD('to', 'date(smartTime)', 'caption=До,after=from,single=none,mandatory');

    }
    /**
     * @return string
     */
    function act_bankPayments()
    {




        $to= dt::today();
        $from = date("Y-m-d", strtotime("2017-03-01"));

$result = self::getBankPayments($from,$to);
       bp($result);

      return self::getBankPayments($from,$to);


    }

    public static function getBankPayments($from,$to){

        //Приходни банкови документи

        $invoiceQuery = sales_Invoices::getQuery();

        $inbankQuery = bank_IncomeDocuments::getQuery();

        $inbankQuery-> where("#state = 'pending' OR #state = 'active'" );

        $inbankQuery->where(array("(#termDate >= '[#1#]' AND #termDate <= '[#2#]') OR #termDate IS NULL", $from, $to . ' 23:59:59'));

        $inbankQuery->orWhere(array("(#valior >= '[#1#]' AND #valior <= '[#2#]') OR #valior IS NULL", $from, $to . ' 23:59:59'));

        while ($inBankDocuments = $inbankQuery->fetch()){

            $id = $inBankDocuments->id;

            $Document = doc_Containers::getDocument($inBankDocuments->containerId);

            $docType = $Document->singleTitle;

            $thread=doc_Threads::fetch($inBankDocuments->threadId);

            $firstDocInst[$id] = doc_Threads::getFirstDocument($thread->id);

            $className[$id] = $firstDocInst[$id]->className;

            $firstDoc[$id] = $className[$id]::fetch($firstDocInst[$id]->that);

            $date = $inBankDocuments->state == 'activ'? $inBankDocuments->valior: $inBankDocuments->termDate;

            while ($invoices = $invoiceQuery->fetch()){

               if($invoices->threadId == $inBankDocuments->threadId){

                   $invoiceN[$id] = $invoices->number;

                   $invoiceId[$id] = $invoices->id;

               }
            }


            $inbankRes[$docType][$id]=(object)array(

                                                    'type'=>$docType,
                                                    'date'=>$date,
                                                    'amount'=>$inBankDocuments->amount,
                                                    'ownAccountId'=>$inBankDocuments->ownAccount,
                                                    'ownIban'=>bank_OwnAccounts::getTitleById($inBankDocuments->ownAccount),
                                                    'thereAccountId'=>'',
                                                    'thereIban'=>$inBankDocuments->contragentIban,
                                                    'contragentName'=>$inBankDocuments->contragentName,
                                                    'documents'=>''

                                                );

            $inbankRes[$docType][$id]->documents[$firstDocInst[$id]->singleTitle]=$firstDocInst[$id]->that;

            $inbankRes[$docType][$id]->documents['invoiceNo']=$invoiceN[$id];

            $inbankRes[$docType][$id]->documents['invoiceId']=$invoiceId[$id];



        }


        //Разходни банкови документи
        $spendbankQuery = bank_SpendingDocuments::getQuery();

        $spendbankQuery-> where("#state = 'pending' OR #state = 'active'" );

        $spendbankQuery->where(array("(#termDate >= '[#1#]' AND #termDate <= '[#2#]') OR #termDate IS NULL", $from, $to . ' 23:59:59'));

        $spendbankQuery->orWhere(array("(#valior >= '[#1#]' AND #valior <= '[#2#]') OR #valior IS NULL", $from, $to . ' 23:59:59'));

        while ($spendBankDocuments = $spendbankQuery->fetch()){

            $id = $spendBankDocuments->id;

            $Document = doc_Containers::getDocument($spendBankDocuments->containerId);



            $docType = $Document->singleTitle;

            $thread=doc_Threads::fetch($spendBankDocuments->threadId);

            $firstDocInst[$id] = doc_Threads::getFirstDocument($thread->id);

            $className[$id] = $firstDocInst[$id]->className;

            $firstDoc[$id] = $className[$id]::fetch($firstDocInst[$id]->that);

            $date = $spendBankDocuments->state == 'activ'? $spendBankDocuments->valior: $spendBankDocuments->termDate;

            $spendbankRes[$docType][$id]=(object)array(

                                                        'type'=>$docType,
                                                        'date'=>$date,
                                                        'amount'=>$spendBankDocuments->amount,
                                                        'ownAccountId'=>$spendBankDocuments->ownAccount,
                                                        'ownIban'=>bank_OwnAccounts::getTitleById($spendBankDocuments->ownAccount),
                                                        'thereAccountId'=>'',
                                                        'thereIban'=>$spendBankDocuments->contragentIban,
                                                        'contragentName'=>$spendBankDocuments->contragentName,
                                                        'documents'=>''

                                                    );

            $spendbankRes[$docType][$id]->documents[$firstDocInst[$id]->singleTitle]=$firstDocInst[$id]->that;

            $spendbankRes[$docType][$id]->documents['invoiceNo']=$invoiceN[$id];

            $spendbankRes[$docType][$id]->documents['invoiceId']=$invoiceId[$id];


        }

        $sumRes = $inbankRes+$spendbankRes;

       foreach ($sumRes as $v){
           foreach ($v as $vv){
               $res[]=$vv;
           }
           
       }


        return $res;
    }



}
