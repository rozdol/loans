<?php
namespace Test\Rozdol\Loans;

use Rozdol\Loans\Calculator;

use PHPUnit\Framework\TestCase;

class CalulatorTest extends TestCase
{
    
    /**
    * @dataProvider transactiionsProvider
    */

    public function testSum2num($a, $b, $c)
    {

        $result = Calculator::sum2num($a, $b);

        $this->assertEquals($c, $result['result']);
    }
    public function transactiionsProvider()
    {
        return [
            [1,1,2],
            [-1,1,0],
            [1,-1,0],
            [4.5, 1.3, 5.8]

        ];
    }
}
