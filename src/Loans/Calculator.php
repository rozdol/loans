<?php
namespace Rozdol\Loans;

class Calculator
{
    /**
     * get random number
     *
     * @return array
     */
    public static function sum2num($a, $b)
    {
        $b = $a + $b;
        return [
            'result' => $b,
            'rnd' => rand()
        ];
    }
}
