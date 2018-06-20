<?php
namespace Rozdol\Loans;

use Rozdol\Dates\Dates;
use Rozdol\Loans\interest;
use Rozdol\Utils\Utils;
use Rozdol\Html\Html;

//use Rozdol\Loans\Planner;

class Loan
{
    private static $hInstance;

    public static function getInstance()
    {
        if (!self::$hInstance) {
            self::$hInstance = new Loan();
        }
        return self::$hInstance;
    }

    public function __construct()
    {
            $this->dates = new Dates();
            $this->interest = new Interest();
            $this->utils = new Utils();
            $this->html = new Html();
            $this->planner = new Planner();
    }

    public function getInterest($data)
    {
        return $this->interest->getInterest($data);
    }

    public function planLoan($data)
    {
        return $this->planner->planLoan($data);
    }
    public function calcPmt($data)
    {
        //unset($data);
        //$data['amount']=234000;
        //$data['period_rate']=0.035/12;
        //$data['payments']=240;
        //echo $this->pre_display($data,'calc_pmt');
        //M = monthly mortgage payment
        //P = the principal, or the initial amount you borrowed.
        //r = your monthly interest rate. Your lender likely lists interest rates as an annual figure, so you’ll need to divide by 12, for each month of the year. So, if your rate is 5%, then the monthly rate will look like this: 0.05/12 = 0.004167.
        //n = the number of payments, or the payment period in months. If you take out a 30-year fixed rate mortgage, this means: n = 30 years x 12 months per year, or 360 payments.

        $p=$data['amount'];
        $r=$data['period_rate'];
        $n=$data['payments'];

        //$M = $P*( $r*(1 + $r)^$n ) / ((1 + $r)^$n – 1);

        $m1=$r*(pow((1+$r), $n));
        $m2=pow((1+$r), $n)-1;
        $m=$p*($m1/$m2);

        return $m;
    }
    /**
     * calc Loan
     *
     * @return array
     */
    public function calcLoan($data)
    {
        $GLOBALS[debug][stopwatch]='calc_loan';
        if ($GLOBALS[access][view_debug]) {
            $data[domain]=$GLOBALS[debug][stopwatch];
            $GLOBALS[debug][calc_loan][]=$data;
        }

        //echo $this->pre_display($data,'data'); //exit;
        $totals=array_fill(0, 19, 0);
        $expired=0;
        if ($data[loan_data][base]=='365') {
            $daysinyear=365;
        } else {
            $daysinyear=360;
        }
        if ($data[loan_data][base]=='') {
            $data[loan_data][base]='30/360';
        }

        $res=$data;
        $res[err]='';
        // initial data
        $loan_data=$res[loan_data];
        $res[days]=$this->dates->F_datediff($loan_data[df], $loan_data[dt], $loan_data[base]);
        $base=$loan_data[base];
        $rate=$loan_data[rate];
        $freq=$loan_data[freq];
        $days_allowed=$daysinyear/$freq;

        $max_amount=$loan_data[amount];
        $date=$this->dates->F_date($loan_data[date], 1);

        $data=array(
            'amount'=>$loan_data[amount],
            'rate'=>$loan_data[rate],
            'freq'=>$loan_data[freq],
            'df'=>$loan_data[df],
            'dt'=>$loan_data[dt],
            'base'=>$loan_data[base],
            'compound'=>$loan_data[compound],
            'note'=>'Calc Loan. Initial',
        );

        $whole_loan=$this->getInterest($data);


        $transactions=$res[transactions];
        $bal=$transactions[0][given];
        $notes=$transactions[0][descr];
        $last_intpaid=$transactions[0][date];

        $fields=array('#','Action','date','Given','returned','int.Paid','int.adj.','Balance','Ineterest','rate','days','');
        $tbl=$this->html->tablehead($what, $qry, $order, $addbutton, $fields, $sort);
        //transactions
        $GLOBALS[debug][stopwatch]='calc_loan_main';
        foreach ($transactions as $transaction) {
            $type='';
            $i++;
            $descr='';
            $class='';
            if ($transaction[given]>0) {
                $descr.='Give';
                $type='GIV';
            }
            if ($transaction[returned]>0) {
                $descr.='Return';
                $type='RET';
            }
            if ($transaction[paid]>0) {
                $descr.='Pay';
                $last_intpaid=$transaction[date];
                $type='INT';
            }
            if ($transaction[adjustment]!=0) {
                $descr.='Interest Adjustment';
                $last_intpaid=$transaction[date];
                $type='ADJ';
            }
            if (($transaction[given]==0)&&($transaction[returned]==0)&&($transaction[paid]==0)&&($transaction[adjustment]==0)) {
                $type='ADD';
                $descr.='Addendum';
                //$rate=$transaction[rate];
                $freq=$transaction[freq];
                $days_allowed=$daysinyear/$freq;
            }
            $totals[2]+=$transaction[given];
            $totals[3]+=$transaction[returned];
            $totals[4]+=$transaction[paid];
            $totals[5]+=$transaction[adjustment];


            //echo "$i. $transaction[date] $descr B:$bal";
            if ($i==1) {
                //first transaction
                $amount=$transaction[given]-$transaction[returned];
                $loan[dt]=$transaction[date];
                $amount1=$amount;
                //echo $this->pre_display($loan, "$i $loan[dt] $descr");
                $tbl.="<tr class='$class'><td>$i</td>
                <td>$descr</td>
                <td>$loan[dt]</td>
                <td class='n'>".$this->html->money($transaction[given])."</td>
                <td class='n'>".$this->html->money($transaction[returned])."</td>
                <td class='n'>".$this->html->money($transaction[paid])."</td>
                <td class='n'>".$this->html->money($transaction[paid])."</td>
                <td class='n'>".$this->html->money($transaction[paid])."</td>
                <td class='n'>".$this->html->money($transaction[paid])."</td>
                <td class='n'>".$this->html->money($rate*100)." %</td>
                <td class='n'>$loan[days]</td>
                <td>$notes</td>
                </tr>";
                $totals[9]+=$loan[days];
                $plan[$i]=array(
                    'no'=>$i,
                    'action'=>'Pay',
                    'date'=>$loan[dt],
                    'given'=>$transaction[given],
                    'returned'=>$transaction[returned],
                    'int_paid'=>$transaction[paid],
                    'balance'=>$transaction[paid],
                    'interest'=>$transaction[paid],
                    'rate'=>$rate,
                    'days'=>$loan[days],
                    't_given'=>$data[amount],
                    't_returned'=>$t_principal_paid,
                    't_interest'=>$t_interest_paid,
                    't_paid'=>$t_interest_paid+$t_principal_paid,
                    'info'=>$notes,
                    );
            } else {
                //expired period
                $days=$this->dates->F_datediff($transaction[date], $loan_data[dt]);
                if (($days<0)&&($bal>0)&&($expired==0)) {
                    $expired++;
                    $i++;
                    $rate=$loan_data[p_rate];
                    $class='red';
                    $df=$loan[dt];
                    $dt=$loan_data[dt];
                    $data=array(
                        'amount'=>$bal,
                        'rate'=>$rate,
                        'freq'=>$freq,
                        'df'=>$df,
                        'dt'=>$dt,
                        'base'=>$base,
                        'compound'=>$loan_data[compound],
                        'note'=>'Calc Loan. Expired',
                    );
                    $descr="Expired" ;

                    $loan=$this->getInterest($data);

                    if ($loan_data[compound]=='f') {
                        //$bal=round(($loan[balance]+$transaction[given]-$transaction[returned]),2);
                        $int_bal=$int_bal+$loan[interest]-$transaction[paid]-$transaction[adjustment];
                    } else {
                        $bal=round(($loan[balance]+$transaction[given]-$transaction[returned]-$transaction[paid]-$transaction[adjustment]), 2);
                        //$int_bal=$loan[interest];
                    }

                    //$bal=round(($bal+$loan[interest]),2);


                    $totals[7]+=$loan[interest];
                    //echo $this->pre_display($loan, "$i $dt $descr");
                    $tbl.="<tr class='$class'><td>$i</td>
                    <td>$descr</td>
                    <td>$dt</td>
                    <td class='n'>".$this->html->money(0)."</td>
                    <td class='n'>".$this->html->money(0)."</td>
                    <td class='n'>".$this->html->money(0)."</td>
                    <td class='n'>".$this->html->money(0)."</td>
                    <td class='n'>".$this->html->money($bal)."</td>
                    <td class='n'>".$this->html->money($loan[interest])."</td>
                    <td class='n'>".$this->html->money($rate*100)." %</td>
                    <td class='n'>$loan[days]</td>
                    <td>Expired period</td>
                    </tr>";
                    $totals[9]+=$loan[days];
                    $plan[$i]=array(
                        'no'=>$i,
                        'action'=>$descr,
                        'date'=>$dt,
                        'given'=>0,
                        'returned'=>0,
                        'int_paid'=>0,
                        'balance'=>$bal,
                        'interest'=>$loan[interest],
                        'rate'=>$rate,
                        'days'=>$loan[days],
                        't_given'=>$data[amount],
                        't_returned'=>$t_principal_paid,
                        't_interest'=>$t_interest_paid,
                        't_paid'=>$t_interest_paid+$t_principal_paid,
                        'info'=>'Expired period',
                        );
                }
                if ($transaction[given]>0) {
                    $descr='Give';
                    $type='GIV';
                }
                if ($transaction[returned]>0) {
                    $descr='Return';
                    $type='RET';
                }
                if ($transaction[paid]>0) {
                    $descr='Pay';
                    $last_intpaid=$transaction[date];
                    $type='INT';
                }
                if ($transaction[adjustment]!=0) {
                    $descr='Interest Adjustment';
                    $last_intpaid=$transaction[date];
                    $type='ADJ';
                }
                if (($transaction[given]==0)&&($transaction[returned]==0)&&($transaction[paid]==0)&&($transaction[adjustment]==0)) {
                    $type='ADD';
                    $descr='Addendum';
                    //$rate=$transaction[rate];
                    $freq=$transaction[freq];
                    $days_allowed=$daysinyear/$freq;
                }
                //rest transactions
                $df=$loan[dt];
                $dt=$transaction[date];
                $days=$this->dates->F_datediff($df, $loan_data[dt]);
                $notes=$transaction[descr];
                if (($days<0)&&($bal*.9>0)) {
                    $rate=$loan_data[p_rate];
                    $class='roze';
                    $res[err].="$df Penalty rate of ".($rate*100)." % is applied (Bal:$bal).<br>";
                }
                $data=array(
                    'amount'=>$bal,
                    'rate'=>$rate,
                    'freq'=>$freq,
                    'df'=>$df,
                    'dt'=>$dt,
                    'base'=>$base,
                    'compound'=>$loan_data[compound],
                    'note'=>'Calc Loan. Rest Transactions',
                );
                //echo $this->pre_display($data,'data');
                $loan=$this->getInterest($data);
                //echo $this->pre_display($loan,'loan'); exit;


                //$notes="<span class='badge info'>".$this->html->money($int_bal)." + ".$this->html->money($loan[interest])."</span> $notes";

                if ($loan_data[compound]=='f') {
                    $bal=round(($loan[balance]+$transaction[given]-$transaction[returned]), 2);
                    $int_bal=$int_bal+$loan[interest]-$transaction[paid]-$transaction[adjustment];
                } else {
                    $bal=round(($loan[balance]+$transaction[given]-$transaction[returned]-$transaction[paid]-$transaction[adjustment]), 2);
                    $int_bal=$loan[interest];
                }


                if ($bal>$max_amount*1.2) {
                    $class='red';
                    $notes="$bal>$max_amount Exceeds allowed amount. ".$notes;
                    $res[err].="$df Exceeds allowed amount (Bal:$bal).<br>";
                }
                if ($bal<0) {
                    $class='orange';
                    $notes='Overpaid. '.$notes;
                    $res[err].="$df Overpaid (Bal:$bal).<br>";
                }
                $days_notpaid=$this->dates->F_datediff($last_intpaid, $dt, $base);
                $i_periods=floor($days_notpaid/$days_allowed);
                if (($days_notpaid>$days_allowed)&&($bal>0)&&($i_periods>0)) {
                    $res[err].="$dt Failed to pay interest for $i_periods periods $last_intpaid - $dt (Bal:".round($loan[interest], 2).").<br>";
                }
                //echo $this->pre_display($loan, "$i $dt $descr");
                $tbl.="<tr class='$class'><td>$i</td>
                <td>$descr</td>
                <td>$dt</td>
                <td class='n'>".$this->html->money($transaction[given])."</td>
                <td class='n'>".$this->html->money($transaction[returned])."</td>
                <td class='n'>".$this->html->money($transaction[paid])."</td>
                <td class='n'>".$this->html->money($transaction[adjustment])."</td>
                <td class='n'>".$this->html->money($bal)."</td>
                <td class='n'>".$this->html->money($int_bal)."</td>
                <td class='n'>".$this->html->money($rate*100)." %</td>
                <td class='n'>$loan[days]</td>
                <td>$notes</td>
                </tr>";
                $totals[9]+=$loan[days];
                $plan[$i]=array(
                    'no'=>$i,
                    'action'=>$descr,
                    'date'=>$dt,
                    'given'=>$transaction[given],
                    'returned'=>$transaction[returned],
                    'int_paid'=>$transaction[paid],
                    'balance'=>$bal,
                    'interest'=>$int_bal,
                    'rate'=>$rate,
                    'days'=>$loan[days],
                    't_given'=>$data[amount],
                    't_returned'=>$t_principal_paid,
                    't_interest'=>$t_interest_paid,
                    't_paid'=>$t_interest_paid+$t_principal_paid,
                    'info'=>$notes,
                    );

                if ($type=='ADD') {
                    $rate=$transaction[rate];
                }
            }
            $totals[7]+=$loan[interest];
            //echo ", B2:$bal, days:$days<br>";
        }

        //expired period
        $days=$this->dates->F_datediff($date, $loan_data[dt]);
        if (($days<0)&&($bal>0)&&($expired==0)) {
            $i++;
            $rate=$loan_data[p_rate];
            $class='red';
            $df=$loan[dt];
            $dt=$loan_data[dt];
            $data=array(
                'amount'=>$bal,
                'rate'=>$rate,
                'freq'=>$freq,
                'df'=>$df,
                'dt'=>$dt,
                'base'=>$base,
                'compound'=>$loan_data[compound],
                'note'=>'Calc Loan. Expired Period',
            );
            $descr="Expired" ;
            $loan=$this->getInterest($data);

            if ($loan_data[compound]=='f') {
                //$bal=round(($loan[balance]+$transaction[given]-$transaction[returned]),2);
                $int_bal=$int_bal+$loan[interest]-$transaction[paid];
            } else {
                $bal=round(($loan[balance]+$transaction[given]-$transaction[returned]-$transaction[paid]), 2);
                $int_bal=$loan[interest];
            }

            $totals[7]+=$loan[interest];
            //echo $this->pre_display($data, "DATA: $i $dt $descr"); echo $this->pre_display($loan, "RES: $i $dt $descr");
            $tbl.="<tr class='$class'><td>$i</td>
            <td>$descr</td>
            <td>$dt</td>
            <td class='n'>".$this->html->money(0)."</td>
            <td class='n'>".$this->html->money(0)."</td>
            <td class='n'>".$this->html->money(0)."</td>
            <td class='n'>".$this->html->money(0)."</td>
            <td class='n'>".$this->html->money($bal)."</td>
            <td class='n'>".$this->html->money($loan[interest])."</td>
            <td class='n'>".$this->html->money($rate*100)." %</td>
            <td class='n'>$loan[days]</td>
            <td>Expired period 2</td>
            </tr>";
            $totals[9]+=$loan[days];
            $plan[$i]=array(
                'no'=>$i,
                'action'=>$descr,
                'date'=>$dt,
                'given'=>0,
                'returned'=>0,
                'int_paid'=>0,
                'balance'=>$bal,
                'interest'=>$loan[interest],
                'rate'=>$rate,
                'days'=>$loan[days],
                't_given'=>$data[amount],
                't_returned'=>$t_principal_paid,
                't_interest'=>$t_interest_paid,
                't_paid'=>$t_interest_paid+$t_principal_paid,
                'info'=>'Expired period 2',
                );
        }

        //Up to now
        $i++;
        $class='';
        $descr='Now ';
        $transaction[date]=$date;
        $df=$loan[dt];
        $dt=$transaction[date];
        $days=$this->dates->F_datediff($df, $loan_data[dt]);
        if ($days<=0) {
            $rate=$loan_data[p_rate];
        }
        $data=array(
            'amount'=>$bal,
            'rate'=>$rate,
            'freq'=>$freq,
            'df'=>$df,
            'dt'=>$dt,
            'base'=>$base,
            'compound'=>$loan_data[compound],
            'note'=>'Calc Loan. Up to Now',
        );

        $loan=$this->getInterest($data);
        //echo $this->pre_display($data, "DATA: $i $dt $descr"); echo $this->pre_display($loan, "RES: $i $dt $descr");

        if ($loan_data[compound]=='f') {
            //$bal=round(($loan[balance]+$transaction[given]-$transaction[returned]),2);
            $int_bal=$int_bal+$loan[interest];
        } else {
            //$bal=round(($loan[balance]+$transaction[given]-$transaction[returned]-$transaction[paid]),2);
            $int_bal=$loan[interest];
        }


        if (($days<=0)&&($bal*.9>0)) {
            $rate=$loan_data[p_rate];
            $class='roze';
            $res[err].="$df Penalty rate of ".($rate*100)." % is applied (Bal:$bal).<br>";
        }
        if ($bal<=0) {
            $class='green';
        }
        if ($bal>0) {
            $class='roze';
        }
        if ($bal>100) {
            $class='red';
        }
        if ($int_bal>100) {
            $class='red';
        }
        //$totals[7]+=$loan[interest]+$totals[5];
        $totals[7]=$int_bal;
        $totals[0]=$bal+$totals[7];
        //echo $this->pre_display($loan, "$i $dt $descr");
        $tbl.="<tr class='$class'><td>$i</td>
        <td>$descr</td>
        <td>$dt</td>
        <td class='n'>".$this->html->money(0)."</td>
        <td class='n'>".$this->html->money(0)."</td>
        <td class='n'>".$this->html->money(0)."</td>
        <td class='n'>".$this->html->money(0)."</td>
        <td class='n'>".$this->html->money($bal)."</td>
        <td class='n'>".$this->html->money($int_bal)."</td>
        <td class='n'>".$this->html->money($rate*100)." %</td>
        <td class='n'>$loan[days]</td>
        <td>Up to now </td>
        </tr>";
        $totals[9]+=$loan[days];
        $plan[$i]=array(
            'no'=>$i,
            'action'=>$descr,
            'date'=>$dt,
            'given'=>0,
            'returned'=>0,
            'int_paid'=>0,
            'balance'=>$bal,
            'interest'=>$int_bal,
            'rate'=>$rate,
            'days'=>$loan[days],
            't_given'=>$data[amount],
            't_returned'=>$t_principal_paid,
            't_interest'=>$t_interest_paid,
            't_paid'=>$t_interest_paid+$t_principal_paid,
            'info'=>'Up to now',
            );
        $totals[6]=$bal;
        //echo "$i. $transaction[date] $descr B:$bal<br>";
        $tbl.=$this->html->tablefoot($i, $totals, $i);
        $res[tbl]=$tbl;
        $res[csv]=$this->utils->tbl2csv($tbl);
        $res[data]=$plan;
        $res[given]=$totals[2];
        $res[returned]=$totals[3];
        $res[returned_i]=$totals[4];
        $res[interest]=round($totals[7], 2);
        $res[interest_accrued]=round($res[interest]+$res[returned_i], 2);
        $res[balance]=round($totals[6], 2);
        $res[interest_predict]=round($whole_loan[interest]);
        $res[completion]=round((($res[given]+$res[returned]+$res[returned_i]))/(($whole_loan[amount]*2+$res[interest])), 2);
        $res[completion_prc]=$res[completion]*100;
        $res[progress]=$this->html->draw_progress($res[completion_prc]);

        $res[completion1]=round(($res[given])/($whole_loan[amount]), 2)*100;
        $res[completion2]=round((($res[returned]+$res[returned_i]))/(($res[given]+$res[interest])), 2)*100;
        $res[progress2]=$this->html->draw_progress($res[completion1]).$this->html->draw_progress($res[completion2]);

        $out.= "<table class='table table-morecondensed table-notfull'>";
        $out.="<tr><td class='mr'><b>Completion: </b></td><td class='mt'>$res[completion_prc] %</td></tr>";
        $out.="<tr><td class='mr'><b>Amount: </b></td><td class='mr'>".$this->html->money($whole_loan[amount])."</td></tr>";
        $out.="<tr><td class='mr'><b>Amount given: </b></td><td class='mr'>".$this->html->money($res[given])."</td></tr>";
        $out.="<tr><td class='mr'><b>Amount returned: </b></td><td class='mr'>".$this->html->money($res[returned])."</td></tr>";
        $out.="<tr><td class='mr'><b>Amount interest accrued: </b></td><td class='mr'>".$this->html->money($res[interest_accrued])."</td></tr>";
        $out.="<tr><td class='mr'><b>Amount interest paid: </b></td><td class='mr'>".$this->html->money($res[returned_i])."</td></tr>";
        $out.="<tr><td class='mr'><b>Amount interest due: </b></td><td class='mr'>".$this->html->money($res[interest])."</td></tr>";
        $out.="<tr><td class='mr'><b>Amount interest planned (max): </b></td><td class='mr'>".$this->html->money($res[interest_predict])."</td></tr>";
        $out.="</table>";
        $res[details]=$out;

        return $res;
    }
}