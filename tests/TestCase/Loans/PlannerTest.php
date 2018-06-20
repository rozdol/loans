<?php
namespace Test\Rozdol\Loans;

use Rozdol\Loans\Planner;

use PHPUnit\Framework\TestCase;

class PlannerTest extends TestCase
{
    protected function setUp()
    {
        $this->planner = Planner::getInstance();
        //$this->planner = new Interest();
    }

    // public function testPlanner()
    // {

    //     $result = $this->planner->planLoan($data);
    //     //$this->getActualOutput($result);
    //     fwrite(STDERR, print_r($result, true));
    //     $this->assertEquals(0, $result['given']);
    // }

    /**
    * @dataProvider transactionsProvider
    */

    public function testLoan($data, $expected1, $expected2, $expected3, $expected4)
    {

        $result = $this->planner->planLoan($data);
        unset($result[plan]);
        unset($result[tbl]);
        //fwrite(STDERR, print_r($result, true));
        $this->assertEquals($expected1, $result[amount_total]);
        $this->assertEquals($expected2, $result[days]);
        $this->assertEquals($expected3, $result[rows]);
        $this->assertEquals($expected4, $result[amount_interest_total]);
    }


    public function transactionsProvider()
    {
        $data=array (
        'amount' => '1000000',
        'rate' => 0.10,
        'freq' => '12',
        'df' => '01.01.2020',
        'dt' => '01.01.2021',
        'base' => '30/360',
        'p_rate' => 0.10,
        'date' => '01.01.2020',
        'compound' => 'f',
        'payments' => '12',
        'periods' => 12.0,
        'period_rate' => (.1/12),
        'pmt' => 87915.89,
        'align' => '0',
        );
        $data2=$data;
        $data3=$data;
        $data2[compound]='t';
        $data3[base]='365';
        return [
            [$data,1000000,360,13,54865.13],
            [$data2,1000000,360,13,54990.65],
            [$data3,1000000,366,13,54865.13]

        ];
    }
}
