<?php
namespace Test\Rozdol\Loans;

use Rozdol\Loans\Interest;

use PHPUnit\Framework\TestCase;

class InterestTest extends TestCase
{
    protected function setUp()
    {
        //$this->interest = Interest::getInstance();
        $this->interest = new Interest();
    }

    /**
    * @dataProvider transactionsProvider
    */

    public function testIneterst($data, $expected)
    {

        $result = $this->interest->getInterest($data);

        $this->assertEquals($expected, $result['interest']);
    }

    /**
    * @dataProvider datesProvider
    */

    // public function testDateDiff($df, $dt, $base, $res)
    // {

    //     //$result = Interest::F_datediff($df, $dt, $base);

    //     //$this->assertEquals($res, $result);
    // }

    public function transactionsProvider()
    {
        $data=[
            'amount'=>1000000,
            'rate'=>0.1,
            'freq'=>12,
            'df'=>'01.01.2010',
            'dt'=>'01.01.2011',
            'base'=>'365',
            'compound'=>1,
            'note'=>'Calc Loan test'
        ];
        return [
            [$data, 104713.07]

        ];
    }

    public function datesProvider()
    {
        return [
            ['01.01.2018','01.02.2018','365',31],
            ['01.02.2018','01.03.2018','365',28],
            ['01.02.2020','01.03.2020','365',29],
        ];
    }
}
