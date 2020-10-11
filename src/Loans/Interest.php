<?php
namespace Rozdol\Loans;

use Rozdol\Dates\Dates;

class Interest
{
    private static $hInstance;

    public static function getInstance()
    {
        if (!self::$hInstance) {
            self::$hInstance = new Interest();
        }
        return self::$hInstance;
    }

    public function __construct()
    {
            $this->dates = new Dates();
    }
    /**
     * get Iterest
     *
     * @return array
     */

    public function getInterest($data)
    {
        /*
        0 or omitted    US (NASD) 30/360
        1                Actual/actual
        2                Actual/360
        3                Actual/365
        4                European 30/360
        */
        $df=$data[df];
        $dt=$data[dt];
        //echo \util::pre_display($data,' data from interest'); //exit;

        $daysinyear=($data[base]=='365')?$this->dates->F_daysinyear($res[df]):360;
        if ($data[base]=='') {
            $data[base]='30/360';
        }
        $data[daysinyear]=$daysinyear;
        $res=$data;
        $res[days]=$this->dates->F_datediff($res[df], $res[dt], $res[base]);
        $res[years]=$res[days]/$daysinyear;
        if ($res[compound]=='f') {
            $res[interest]=0;
            $res[df1]=$res[df];
            $res[dt1]="31.12.".substr($res[df], 6, 4);
            if ($this->dates->is_earlier($res[dt], $res[dt1])) {
                $res[dt1]=$res[dt];
            }
            //Calc 1st interest
            $days=$this->dates->F_datediff($res[df], $res[dt1]);
            //$daysinyear=$this->dates->F_daysinyear($res[dt1]);
            $daysinyear=($data[base]=='365')?$this->dates->F_daysinyear($res[dt1]):360;
            $years=$days/$daysinyear;
            $int=$res[amount]*$res[rate]*$years;
            $res[interest]+=$int;
            if ($int>0) {
                $res[formula].="($days / $daysinyear x $res[amount] x $res[rate]) = [".round($int, 2)."] + ";
            }

            $res[days_calc]+=$days;
            if ($this->dates->is_earlier($res[dt1], $res[dt])) {
                $res[debug].="Continue | ";
                $res[dt2]=$this->dates->F_dateadd($res[dt1], 1);
                $res[dt3]=$this->dates->F_dateadd_year($res[dt2], -1);

                $min_date=strtotime($res[dt3]);
                $max_date=strtotime($res[dt]);

                while (($min_date = strtotime("+1 YEAR", $min_date)) <= $max_date) {
                    $no++;
                    $date=date('d.m.Y', $min_date);
                    $date_to=$this->dates->F_dateadd($this->dates->F_dateadd_year($date, 1), -1);
                    $date_from=$date;
                    //$daysinyear=$this->dates->F_daysinyear($date_to);
                    $daysinyear=($data[base]=='365')?$this->dates->F_daysinyear($date_to):360;
                    if ($this->dates->is_earlier($res[dt], $date_to)) {
                        $date_to=$res[dt];
                    }
                    $days=$this->dates->F_datediff($date_from, $date_to)+1;
                    $years=$days/$daysinyear;
                    $int=$res[amount]*$res[rate]*$years;
                    $res[interest]+=$int;

                    $res[days_calc]+=$days;
                    $res[debug].="Loop$no : ($days/$daysinyear) $date_from - $date_to | ";
                    if ($int>0) {
                        $res[formula].="($days / $daysinyear x $res[amount] x $res[rate]) = [".round($int, 2)."] + ";
                    }
                }
            }
            $res[balance]=$res[amount];
            //if(($GLOBALS[debug][stopwatch]=='calc_loan_main')){echo $this->pre_display($res,'res'); exit;}

            if ($GLOBALS[access][view_debug]) {
                $res[domain]=$GLOBALS[debug][stopwatch];
                $GLOBALS[debug][calc_interest][]=$res;
            }
        } else {
            $res[interest]=$res[amount]*pow((1+$res[rate]/$res[freq]), ($res[freq]*$res[years]))-$res[amount];
            $res[interest]=round($res[interest], 2);
            $res[balance]=$res[amount]+$res[interest];
        }
        $res[formula]=substr($res[formula], 0, -3);
        //echo $this->pre_display($res,"Calc Interest $res[note]");
        $res[csv]=implode(';', $res);
        return $res;
    }
}
