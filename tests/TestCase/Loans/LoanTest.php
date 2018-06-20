<?php
namespace Test\Rozdol\Loans;

use Rozdol\Loans\Loan;

use PHPUnit\Framework\TestCase;

class LoanTest extends TestCase
{
    protected function setUp()
    {
        //$this->loan = Loan::getInstance();
        $this->loan = new Loan();
    }

    // public function testLoan()
    // {

    //     $result = $this->loan->calcLoan($data);
    //     //$this->getActualOutput($result);
    //     fwrite(STDERR, print_r($result, true));
    //     $this->assertEquals(0, $result['given']);
    // }

    /**
    * @dataProvider transactionsProvider
    */

    public function testLoan($data, $expected1, $expected2, $expected3)
    {

        $result = $this->loan->calcLoan($data);
        //$this->getActualOutput($result);
        //fwrite(STDERR, print_r($result, true));
        $this->assertEquals($expected1, $result[given]);
        $this->assertEquals($expected2, $result[interest_accrued]);
        $this->assertEquals($expected3, $result[interest_predict]);
    }


    public function transactionsProvider()
    {
        $data=array (
              'loan_data' =>
              array (
                'amount' => '20000000',
                'rate' => 0.02,
                'freq' => '0',
                'df' => '24.06.2016',
                'dt' => '31.12.2018',
                'base' => '365',
                'p_rate' => 0.02,
                'date' => '15.06.2018',
                'compound' => 'f',
              ),
              'transactions' =>
              array (
                '0' =>
                array (
                  'date' => '24.06.2016',
                  'given' => 4540000,
                  'returned' => 0,
                  'paid' => 0,
                  'adjustment' => 0,
                  'descr' => ' Loan Drawdown',
                ),
                '1' =>
                array (
                  'date' => '15.07.2016',
                  'given' => 5006100,
                  'returned' => 0,
                  'paid' => 0,
                  'adjustment' => 0,
                  'descr' => ' Loan Drawdown',
                ),
                '2' =>
                array (
                  'date' => '20.07.2016',
                  'given' => 4198620,
                  'returned' => 0,
                  'paid' => 0,
                  'adjustment' => 0,
                  'descr' => ' Loan Drawdown',
                ),
                '3' =>
                array (
                  'date' => '22.07.2016',
                  'given' => 4958100,
                  'returned' => 0,
                  'paid' => 0,
                  'adjustment' => 0,
                  'descr' => ' Loan Drawdown',
                ),
                '4' =>
                array (
                  'date' => '26.07.2016',
                  'given' => 1297180,
                  'returned' => 0,
                  'paid' => 0,
                  'adjustment' => 0,
                  'descr' => ' Loan Drawdown',
                ),
                '5' =>
                array (
                  'date' => '31.12.2016',
                  'given' => 0,
                  'returned' => 0,
                  'paid' => 187178.76,
                  'adjustment' => 0,
                  'descr' => ' Interest Paid',
                ),
                '6' =>
                array (
                  'date' => '31.12.2016',
                  'given' => 0,
                  'returned' => 20000000,
                  'paid' => 0,
                  'adjustment' => 0,
                  'descr' => ' Loan Paid',
                ),
                '7' =>
                array (
                  'date' => '31.12.2016',
                  'given' => 0,
                  'returned' => 0,
                  'paid' => 0,
                  'adjustment' => -1092.90,
                  'descr' => ' ',
                ),
              ),
            );
        $data2=array (
        'loan_data' =>
        array (
        'amount' => '1000000',
        'rate' => 0.10000000000000001,
        'freq' => '12',
        'df' => '01.01.2018',
        'dt' => '01.01.2019',
        'base' => '30/360',
        'p_rate' => 0.10000000000000001,
        'date' => '01.01.2019',
        'compound' => 'f',
        ),
        'transactions' =>
        array (
        0 =>
        array (
        'date' => '01.07.2018',
        'given' => 1000000,
        'returned' => 0,
        'paid' => 0,
        'adjustment' => 0,
        'descr' => ' ',
        ),
        ),
        );
        $data3=$data2;
        $data3[loan_data][compound]='t';
        return [
            [$data, 20000000,187178.76,1007650],
            [$data2, 1000000,50410.96,100000],
            [$data3, 1000000,51053.31,104713]

        ];
    }
}
