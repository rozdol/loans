<?php
namespace Rozdol\Loans;

use Rozdol\Dates\Dates;
use Rozdol\Loans\interest;
use Rozdol\utils\Utils;
use Rozdol\Html\Html;

class Planner
{
    private static $hInstance;

    public static function getInstance()
    {
        if (!self::$hInstance) {
            self::$hInstance = new Planner();
        }
        return self::$hInstance;
    }

    public function __construct()
    {
        $this->dates = new Dates();
        $this->interest = new Interest();
        $this->utils = new Utils();
        $this->html = new Html();
    }
    /**
     * calc Loan
     *
     * @return array
     */
    public function planLoan($data)
    {
        $GLOBALS[debug][stopwatch]='plan_loan';
    //echo $this->pre_display($data,"f:plan_loan");
    //$days_add=$this->dates->F_datediff($data[df],$data[dt]);
    //echo "$days_add<br>";
        $res=$data;
        if ($data[base]=='365') {
            $daysinyear=365;
        } else {
            $daysinyear=360;
        }
        if ($data[base]=='') {
            $data[base]='30/360';
        }
        $periods=$data['periods'];
        if ($periods==0) {
            $periods=1;
        }
        $payments=$data['payments'];
        $payment_range=round($periods/$payments);
        if (($payment_range==0)||($payment_range==INF)) {
            $payment_range=1;
        }
        $days_loan=$this->dates->F_datediff($data[df], $data[dt], $data[base]);
        $data[days_loan]=$days_loan;
        $months_loan=$days_loan/365*12;
        $pays_per_year=$data[freq];

        if (($days_loan>366)&&($data[freq]==1)&&($data[periods]==1)) {
            $pays_per_year=365/$days_loan;
        }
        $data[pays_per_year]=$pays_per_year;
        $data[months_loan]=$months_loan;
        $months_loan_rounded=round($months_loan);
        $data[months_loan_rounded]=$months_loan_rounded;
    //echo $this->pre_display($data,"F:plan_loan");
    //echo "D:$payment_range<br>";
        $fields=array('#','Action','date','Given','Payment','Pcpl. paid','int.Paid','Balance','Ineterest','rate','days','');
        $tbl=$this->html->tablehead($what, $qry, $order, $addbutton, $fields, $sort);
        $i=0;
        $no=1;
        $date=$data[df];
        $pmt_amount=$data[pmt];
        if ($payment_range>1) {
            $pmt_amount=round($data[amount]/$payments, 2);
        }
        $pmt=0;
        $balance=$data[amount];
        $tbl.="<tr class='$class'><td>$no</td>
<td>Loan</td>
<td>$date</td>
<td class='n'>".$this->html->money($data[amount])."</td>
<td class='n'>".$this->html->money(0)."</td>
<td class='n'>".$this->html->money(0)."</td>
<td class='n'>".$this->html->money(0)."</td>
<td class='n'>".$this->html->money($balance)."</td>
<td class='n'>".$this->html->money(0)."</td>
<td class='n'>".$this->html->money($data['rate']*100)." %</td>
<td class='n'>0</td>
<td>$notes</td>
</tr>";
        $found=0;
        if ($this->dates->is_earlier($data['date'], $data['df'])) {
            $found=1;
        }

        $plan[]=array(
        'no'=>$no,
        'action'=>'Loan',
        'date'=>$date,
        'given'=>$data[amount],
        'returned'=>0,
        'int_paid'=>0,
        'balance'=>$balance,
        'interest'=>0,
        'rate'=>$data['rate'],
        'days'=>0,
        't_given'=>$data[amount],
        't_returned'=>0,
        't_interest'=>0,
        't_paid'=>0,
        'info'=>'',
        );
        if ($data['align']>0) {
            $no++;
            $date_prev=$date;
            if ($data['align']<32) {
                $day=substr($date, 0, 2);
                //echo "$day<br>";
                if ($data['align']>=$day) {
                    $days_add=$data['align']-$day;
                } else {
                    $days_add=$data['align']-$day + $this->days_in_month($date);
                }
                $align_text="$data[align] day of the month";
            } else {
                $days_add=$this->dates->F_datediff($date, $this->dates->lastday_in_month($date));
                $align_text="the last day of the month";
            }
    
            $date=$this->dates->F_dateadd($date, $days_add);
            $data4interest=array(
            'amount'=>$balance,
            'rate'=>$data['rate'],
            'freq'=>$data['freq'],
            'df'=>$date_prev,
            'dt'=>$date,
            'base'=>$data[base],
            'compound'=>$data[compound],
            'note'=>'Plan Loan. Allign.',
            );

            $calc_interest=$this->interest->getInterest($data4interest);
            $interest=$calc_interest[interest];
            if ($pmt>0) {
                $amount=$pmt-$interest;
            } else {
                $amount=0;
            }
            $balance_prev=$balance;
            $balance=$balance-$amount;
            $days=$this->dates->F_datediff($date_prev, $date, $data[base]);
            $days_chk=$this->dates->F_datediff($date, $data['date']);
    
            $notes='';
            //$notes=$this->pre_display($calc_interest);
            if (($days_chk<=0)&&($found==0)) {
                $found=1;
                $res[balance]=$balance_prev;
                $res[interest]=$interest;
                $res[t_paid]=$t_paid;
                $res[t_interest_paid]=$t_interest_paid;
                $res[t_principal_paid]=$t_principal_paid;
                $res[next_payment]=$date;
                $res[days_till_next]=-$days_chk;
                //$info=$this->pre_display($res);
                //$notes="<span class='badge red'>DATE $data[date]</span> $info";
            }
            $t_paid+=$pmt;
            $t_interest_paid+=$interest;
            $t_principal_paid+=$amount;
            $total=$amount+$interest;
            $t_days+=$days;
            $tbl.="<tr class='$class'><td>$no</td>
	<td>Pay</td>
	<td>$date</td>
	<td class='n'>".$this->html->money(0)."</td>
	<td class='n'>".$this->html->money($total)."</td>
	<td class='n'>".$this->html->money($amount)."</td>
	<td class='n'>".$this->html->money($interest)."</td>
	<td class='n'>".$this->html->money($balance)."</td>
	<td class='n'>".$this->html->money($interest)."</td>
	<td class='n'>".$this->html->money($data['rate']*100)." %</td>
	<td class='n'>$days</td>
	<td>Aligned to $align_text $notes</td>
	</tr>";
            $plan[]=array(
            'no'=>$no,
            'action'=>'Pay',
            'date'=>$date,
            'given'=>0,
            'returned'=>$amount,
            'int_paid'=>$interest,
            'balance'=>$balance,
            'interest'=>$interest,
            'rate'=>$data['rate'],
            'days'=>$days,
            't_given'=>$data[amount],
            't_returned'=>$t_principal_paid,
            't_interest'=>$t_interest_paid,
            't_paid'=>$t_interest_paid+$t_principal_paid,
            'info'=>'',
                );
        }
    //if($pays_per_year>1)$months=12/$months_loan; else $months=12;
        if ($pays_per_year>1) {
            $months=12/$pays_per_year;
        } else {
            $months=12;
        }
    //echo "pays_per_year=$pays_per_year / $months_loan ($months)<br>";
        for ($i=1; $i<=$periods; $i++) {
            $no++;
            $date_prev=$date;
            if ($months>1) {
                //echo "$months / $days_loan<br>";
                if ($pays_per_year>1) {
                    $date=$this->dates->F_dateadd_month($date, $months);
                } else {
                    $date=$this->dates->F_dateadd($date, $days_loan);
                    //echo "DL:$days_loan<br>";
                }
            
                if ($days_add>0) {
                    $date=$this->dates->lastday_in_month($this->dates->F_dateadd($date, -15));
                }
                if (($days_add>0)&&($i==$periods)) {
                    $date=$this->dates->F_dateadd($date, -$days_add);
                }
                //if($no>=5)echo $this->pre_display(['Date'=>$date,'Days_add'=>$days_add],"result4");
            } else {
                $days_in_month=$this->dates->days_in_month_date($this->dates->F_dateadd($date, 15));
                if (($days_add>0)&&($i==$periods)) {
                    $days_in_month=$days_in_month-$days_add;
                }
                $date=$this->dates->F_dateadd($date, $days_in_month);
                //$date=$this->dates->F_dateadd_month($date,$months);
            }

            $days_chk=$this->dates->F_datediff($date, $data['date']);
    
            $notes='';
            //$notes=$this->pre_display($calc_interest);
            if (($days_chk<=0)&&($found==0)) {
                $data4interest=array(
                'amount'=>$balance,
                'rate'=>$data['rate'],
                'freq'=>$data['freq'],
                'df'=>$date_prev,
                'dt'=>$data[date],
                'base'=>$data[base],
                'compound'=>$data[compound],
                'note'=>'Plan Loan. Main No '.$no,
                );

                $calc_interest=$this->interest->getInterest($data4interest);
                $found=1;
        
                $res[balance]=$balance_prev;
                $res[interest]=$calc_interest[interest];
                $res[t_paid]=$t_paid;
                $res[t_interest_paid]=$t_interest_paid+$res[interest];
                $res[t_principal_paid]=$t_principal_paid;
                $res[next_payment]=$date;
                $res[days_till_next]=-$days_chk;
                //$info=$this->pre_display($res);
                $info.="<table>
		<tr><td>Balance:</td><td class='n'>".$this->html->money($res[balance])."</td></tr>
		<tr><td>Interest:</td><td class='n'>".$this->html->money($res[interest])."</td></tr>
		<tr><td>Interest acc:</td><td class='n'>".$this->html->money($res[t_interest_paid])."</td></tr>
		<tr><td>DTNP:</td><td class='n'>$res[days_till_next]</td></tr>
		</table>";
                $notes="<span class='label'>DATE $data[date]</span><br><span class=''>$info</span>";
            }

            $data4interest=array(
            'amount'=>$balance,
            'rate'=>$data['rate'],
            'freq'=>$data['freq'],
            'df'=>$date_prev,
            'dt'=>$date,
            'base'=>$data[base],
            'compound'=>$data[compound],
            'note'=>'Plan Loan. Final',
            );

            $calc_interest=$this->interest->getInterest($data4interest);
            $interest=$calc_interest[interest];
            $pmt=(($i%$payment_range)==0)?$pmt_amount:0;
            //echo "I.$i=".($i%$payment_range)."<br>";
            if (($pmt==0)&&($i==$periods)) {
                $pmt=$balance+$interest;
            }
            if ($pmt>0) {
                $amount=$pmt-$interest;
            } else {
                $amount=0;
            }
            if (($payment_range>1)&&($pmt>0)) {
                $amount=$pmt;
            }
            $balance_prev=$balance;
            $balance=$balance-$amount;
            $days=$this->dates->F_datediff($date_prev, $date, $data[base]);

            $t_paid+=$pmt;
            $t_interest_paid+=$interest;
    
            $t_principal_paid+=$amount;
            $total=$amount+$interest;
            $t_total_paid+=$total;
            $t_days+=$days;
            $tbl.="<tr class='$class'><td>$no</td>
	<td>Pay</td>
	<td>$date</td>
	<td class='n'>".$this->html->money(0)."</td>
	<td class='n'>".$this->html->money($total)."</td>
	<td class='n'>".$this->html->money($amount)."</td>
	<td class='n'>".$this->html->money($interest)."</td>
	<td class='n'>".$this->html->money($balance)."</td>
	<td class='n'>".$this->html->money($interest)."</td>
	<td class='n'>".$this->html->money($data['rate']*100)." %</td>
	<td class='n'>$days</td>
	<td>$notes</td>
	</tr>";
            $plan[]=array(
            'no'=>$no,
            'action'=>'Pay',
            'date'=>$date,
            'given'=>0,
            'returned'=>$amount,
            'int_paid'=>$interest,
            'balance'=>$balance,
            'interest'=>$interest,
            'rate'=>$data['rate'],
            'days'=>$days,
            't_given'=>$data[amount],
            't_returned'=>$t_principal_paid,
            't_interest'=>$t_interest_paid,
            't_paid'=>$t_interest_paid+$t_principal_paid,
            'info'=>'',
                );
        }
        $totals=array_fill(0, 10, 0);
        $totals[2]=$data[amount];
        $totals[3]=$t_total_paid;
        $totals[4]=$t_principal_paid;
        $totals[5]=$t_interest_paid;
    //$totals[6]=$balance;
        $totals[9]=$t_days;
        $tbl.=$this->html->tablefoot($i, $totals, $no);
        $res[rows]=$no;
        $res[amount_total]=$plan[$no-1][t_given];
        $res[amount_returned_total]=$plan[$no-1][t_returned];
        $res[amount_interest_total]=$plan[$no-1][t_interest];
        $res[amount_paid_total]=$plan[$no-1][t_paid];
        if (($res[interest]==0)&&($found==0)) {
            $res[interest]=$res[amount_interest_total];
        }


        if ($found==0) {
            $res[balance]=$res[amount_total]-$res[amount_returned_total];
            $res[interest]=$interest;
            $res[t_paid]=$res[amount_paid_total];
            $res[t_interest_paid]=$res[amount_interest_total];
            $res[t_principal_paid]=$res[amount_returned_total];
            $res[next_payment]=$dt;
            $res[days_till_next]=0;
        }
        $res[amount_returned_total]=round($res[amount_returned_total], 2);
        $res[amount_interest_total]=round($res[amount_interest_total], 2);
        $res[balance]=round($res[balance], 2);
        $res[interest]=round($res[interest], 2);
        $res[t_paid]=round($res[t_paid], 2);
        $res[t_interest_paid]=round($res[t_interest_paid], 2);
        $res[t_principal_paid]=round($res[t_principal_paid], 2);
        $res[amount_paid_total]=round($res[amount_paid_total], 2);

        $res[days]=$t_days;
        $res[tbl]=$tbl;
        $res[plan]=$plan;

        return $res;
    }
}
