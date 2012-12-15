<?php

class acc_journal_TransactionTest extends PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $id = 1;
        
        expect($rec = cash_Pko::fetch($id));
        
        // Намираме класа на контрагента
        $contragentId = doc_Folders::fetchCoverId($rec->folderId);
        $contragentClass = doc_Folders::fetchCoverClassName($rec->folderId);
        
        $transaction = new acc_journal_Transaction(
            array(
                'reason' => $rec->reason, // основанието за ордера
                'valior' => $rec->date,   // датата на ордера
                'entries' => array(
                    array(
                        'amount' => $rec->rate * $rec->amount,	// равностойноста на сумата в основната валута
                        
                        'debitAcc' => cash_Pko::$caseAccount, // дебитната сметка
                        'debitItem1' => array('cls'=>'cash_Cases', 'id'=>$rec->peroCase),  // перо каса
                		'debitItem2' => array('cls'=>'currency_Currencies', 'id'=>$rec->currencyId),// перо валута
                        'debitQuantity' => $rec->amount,
                        'debitPrice' => $rec->rate,
                		
                        'creditAccId' => $rec->creditAccounts, // кредитна сметка
                        'creditItem1' => array('cls'=>$contragentClass, 'id'=>$contragentId), // перо контрагент
                        'creditItem2' => array('cls'=>'currency_Currencies', 'id'=>$rec->currencyId), // перо валута
                        'creditQuantity' => $rec->amount,
                        'creditPrice' => $rec->rate,
                    )
                )
            )
        );
    }

    public function testBuildSteps()
    {
        $id = 1;
        
        expect($rec = cash_Pko::fetch($id));
        
        // Намираме класа на контрагента
        $contragentId = doc_Folders::fetchCoverId($rec->folderId);
        $contragentClass = doc_Folders::fetchCoverClassName($rec->folderId);
        
        $transaction = new acc_journal_Transaction();
        
        $transaction
            ->add()
            ->setDebit(
                array(
                    'account' => acc_journal_Account::system(cash_Pko::$caseAccount),
                    'items'   => array(
                        array('cash_Cases', $rec->peroCase),
                        array('currency_Currencies', $rec->currencyId),
                    ),
                    'quantity' => $rec->amount,
                    'price'    => $rec->rate,
                )
            )
            ->setCredit(
                array(
                    'account' => acc_journal_Account::id($rec->creditAccounts),
                    'items'   => array(
                        array($contragentClass, $contragentId),
                        array('currency_Currencies', $rec->currencyId),
                    ),
                    'quantity' => $rec->amount,
                    'price'    => $rec->rate,
                )
            );
        
        $this->assertTrue($transaction->check());
        
    }
}