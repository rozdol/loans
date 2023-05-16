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
    public function getDates($data)
    {
        // echo $this->html->pre_display($data,"getDates data");
        $found=0;
        $periods=$data['periods'];
        $ignore_weekends=$data['ignore_weekends'];
        $use_eoy=$data['use_eoy'];

        if ($periods==0) {
            $periods=1;
        }


        if ($data['align']>0) $periods++;
        $data['periods']=$periods;
        $payments=$data['payments'];
        $payment_range=round($periods/$payments);
        //echo "payment_range1=$payment_range=round($periods/$payments);<br>";

        if (($payment_range==0)||($payment_range==INF)) {
            $payment_range=1;
        }
        $data[payment_range]=$payment_range;
        //echo $this->html->pre_display($payment_range,"payment_range");
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
        $item_no=0;
        $no=1;
        $date=$data[df];
        $date_initial=$data[df];
        $date_prev=$date;

        //Check for date_check
        $data['date']=$this->dates->F_date($data['date'],1);
        $days_chk=$this->dates->F_datediff($data['date'], $date_initial);
        //echo $this->html->pre_display($days_chk,"days_chk");
        if (($days_chk<=0)&&($found==0)) {
            $found=1;
            $period_data_chk=[
                'no'=>$item_no,
                'df'=>$date_prev,
                'dt'=>$data['date'],
                'days'=>$this->dates->F_datediff($date_prev, $data['date'], $data[base]),
                't_days'=>$this->dates->F_datediff($date_initial, $data['date'], $data[base]),
                'note'=>'chk',
            ];
            //$period_data_arr[$item_no]=$period_data;
        }

        if ($data['align']>0) {
            $no++;
            $date_prev=$date;

            if ($data['align']<32) {
                //echo "$data[align] ($date)<br>";
                $day=substr($date, 0, 2);
                    //echo "$day<br>";
                if ($data['align']>=$day) {
                    $days_add=$data['align']-$day;
                } else {
                    $days_add=$data['align']-$day + $this->dates->days_in_month($date);
                }
                $align_text="$data[align] day of the month";
            } else {
                $days_add=$this->dates->F_datediff($date, $this->dates->lastday_in_month($date));
                $align_text="the last day of the month";
            }
            $date=$this->dates->F_dateadd_day($date, $days_add,$ignore_weekends);

            $item_no++;
            $period_data=[
                'no'=>$item_no,
                'df'=>$date_prev,
                'dt'=>$date,
                'days'=>$this->dates->F_datediff($date_prev, $date, $data[base]),
                't_days'=>$this->dates->F_datediff($date_initial, $date, $data[base]),
                'note'=>'align',
            ];
            $period_data_arr[$item_no]=$period_data;

            $days=$this->dates->F_datediff($date_prev, $date, $data[base]);

            $t_days+=$days;
            $plan[]=array(
            'no'=>$no,
            'action'=>'Pay',
            'date'=>$date,
            'days'=>$days,
            'info'=>'',
                );
        }
        /// END ========  Align to date


        $date_alligned=$date;

        if ($pays_per_year>1) {
            $months=12/$pays_per_year;
        } else {
            $months=12;
        }
        $data[months]=$months;
        /// ========  Loop for periods
        for ($i=1; $i<=$periods; $i++) {
            $no++;
            $date_prev=$date;
            if ($months>=1) {
                //echo "$months / $days_loan<br>";
                if ($pays_per_year>=1) {
                    $date_before=$date;
                    $date=$this->dates->F_dateadd_month($date_alligned, $months*$i,$ignore_weekends);
                    //echo $this->html->pre_display($pays_per_year,"$date_before - $date pays_per_year $months ($days_add)");
                } else {
                    $date=$this->dates->F_dateadd($date, $days_loan);
                    //echo "DL:$days_loan<br>";
                }

                if (($days_add>0)&&($data['align']==32)) {
                    $date=$this->dates->lastday_in_month($this->dates->F_dateadd($date, -15));
                }
                if (($days_add>0)&&($i==$periods)) {
                    $date=$this->dates->F_dateadd($date, -$days_add);
                }
                //if($no>=5)echo $this->html->pre_display(['Date'=>$date,'Days_add'=>$days_add],"result4");
            } else {
                $days_in_month=$this->dates->days_in_month_date($this->dates->F_dateadd($date, 15));
                if (($days_add>0)&&($i==$periods)) {
                    $days_in_month=$days_in_month-$days_add;
                }
                $date=$this->dates->F_dateadd($date, $days_in_month);
                //$date=$this->dates->F_dateadd_month($date,$months);
            }



            $item_no++;
            $period_data=[
                'no'=>$item_no,
                'df'=>$date_prev,
                'dt'=>$date,
                'days'=>$this->dates->F_datediff($date_prev, $date, $data[base]),
                't_days'=>$this->dates->F_datediff($date_initial, $date, $data[base]),
                'note'=>'maturity',
            ];
            $period_data_arr[$item_no]=$period_data;

            $days=$this->dates->F_datediff($date_prev, $date, $data[base]);

            $t_days+=$days;
        }

        /// END ========  Loop for periods
        $item_no=0;
        $inserted=0;

        $item_no++;
        $period_data_loan=[
            'no'=>$item_no,
            'df'=>$date_initial,
            'dt'=>$date_initial,
            'days'=>0,
            't_days'=>0,
            'note'=>'loan'
        ];
        $period_data_arr2[$item_no]=$period_data_loan;

        $periods=0;
        /// ============  Loop for chk and eoy dates
        foreach ($period_data_arr as $key => $period_data) {
            // check for chk date
            if(($this->dates->is_earlier($period_data_chk[dt],$period_data[dt],0))&&($inserted==0)){
                $inserted=1;
                $item_no++;
                $period_data_chk[df]=$period_data_arr2[$item_no-1][dt];
                $period_data_chk[days]=$this->dates->F_datediff($period_data_chk[df], $period_data_chk[dt], $data[base]);
                $period_data_chk['no']=$item_no;
                $period_data_arr2[$item_no]=$period_data_chk;
            }
            if($use_eoy>0){
                // check for eoy date
                $year=$this->dates->F_extractyear($period_data[df])+1;
                $eoy="01.01.$year";
                //echo $this->html->pre_display($eoy,"eoy");
                if(($this->dates->is_earlier($eoy,$period_data[dt],1))&&($this->dates->is_later($eoy,$period_data[df],1))){
                    $item_no++;
                    $period_data_ny=[
                        'no'=>$item_no,
                        'df'=>$period_data[df],
                        'dt'=>$eoy,
                        'days'=>$this->dates->F_datediff($period_data[df], $eoy, $data[base]),
                        't_days'=>$this->dates->F_datediff($date_initial, $eoy, $data[base]),
                        'note'=>'eoy'
                    ];
                    $period_data_arr2[$item_no]=$period_data_ny;
                    $period_data[df]=$eoy;
                    $period_data['days']=$this->dates->F_datediff($eoy, $period_data[dt], $data[base]);
                    $period_data['note']='align';
                }
            }

            if($use_eom>0){
                // check for eom date
                $year=$this->dates->F_extractyear($period_data[df])+1;
                $month=$this->dates->F_extractmonth($period_data[df])+1;
                $eom="01.$month.$year";
                //echo $this->html->pre_display($eom,"eom");
                if(($this->dates->is_earlier($eom,$period_data[dt],1))&&($this->dates->is_later($eom,$period_data[df],1))){
                    $item_no++;
                    $period_data_nm=[
                        'no'=>$item_no,
                        'df'=>$period_data[df],
                        'dt'=>$eom,
                        'days'=>$this->dates->F_datediff($period_data[df], $eom, $data[base]),
                        't_days'=>$this->dates->F_datediff($date_initial, $eom, $data[base]),
                        'note'=>'eom'
                    ];
                    $period_data_arr2[$item_no]=$period_data_nm;
                    $period_data[df]=$eom;
                    $period_data['days']=$this->dates->F_datediff($eom, $period_data[dt], $data[base]);
                    $period_data['note']='align';
                    echo $this->html->pre_display($period_data_nm,"period_data_nm");
                }
            }
            $item_no++;
            $periods++;
            $period_data['no']=$item_no;
            $period_data_arr2[$item_no]=$period_data;
            $date_prev=$period_data[dt];

        }
        //insert chk date at the end
        if($inserted==0){
            $inserted=1;
            $item_no++;
            $period_data_chk['no']=$item_no;
            $period_data_chk[df]=$period_data_arr2[$item_no-1][dt];
            $period_data_chk[days]=$this->dates->F_datediff($period_data_chk[df], $period_data_chk[dt], $data[base]);
            $period_data_arr2[$item_no]=$period_data_chk;
        }
        $data['periods']=$periods;
        $res=$data;
        $res[period_data]=$period_data_arr2;
        return $res;
    }

    public function getPlanV2($data)
    {
        // echo $this->html->pre_display($data,"getPlanV2 data");
        // echo $this->html->array_display($data[period_data],"period_data");
        $period_data_arr=$data[period_data];
        $balance_prev=$data[amount];
        $interest_accumulated=$data[interest_bf]+$data[default_interest_bf];
        $interest_balance=$data[interest_bf]+$data[default_interest_bf];
        $fields=['#','Action','Date','Days','Given','Payment','Pcpl. paid','Int.paid','Balance','Interest'];

        if($data[default_interest_bf]>0){
            $fields = array_merge($fields, ['Def. interest','Total Interest']); 
        }

        $fields = array_merge($fields, ['Int. Accum.','Interest Bal','Tot.Bal.']); 
        if($data[maturity_id]>0){
            $fields = array_merge($fields, ['Int. rate total','Margin rate','Floating int.','Floating rate','Rate date']); 

        }else{
            $fields = array_merge($fields, ['Int. rate']);;
        }
        if($data[default_interest_bf]>0){
            $fields = array_merge($fields, ['Def. rate']); 
        }

        $fields = array_merge($fields,['']);
        $tbl=$this->html->tablehead($what, $qry, $order, $addbutton, $fields, 'no_sort');
        $m=0;
        $plain_interest=0;
        foreach ($period_data_arr as $key => $period_data) {
            $i++;
            if($period_data[note]=='maturity')$m++;
            $date=$period_data[dt];
            $given=($period_data[note]=='loan')?$data[amount]:0;
            $days=$period_data[days];//$this->dates->F_datediff($date_prev, $date, $data[base]);
            if($period_data[note]!='chk')$t_days+=$days;
            $rate=$period_data['rate'];
            $margin_rate=$data['rate'];
            $data4interest=[
                'amount'=>$balance_prev,
                'rate'=>$period_data['rate'],
                'freq'=>$data['compounding_freq'],
                'df'=>$period_data[df],
                'dt'=>$period_data[dt],
                'base'=>$data[base],
                'compound'=>$data[compound],
                'note'=>$period_data[note],
            ];
            $calc_interest=$this->interest->getInterest($data4interest);
            // echo $this->html->cols2($this->html->pre_display($data4interest,"data4interest $period_data[df] $period_data[dt] $period_data[note]"),$this->html->pre_display($calc_interest,"calc_interest $period_data[df] $period_data[dt] $period_data[note]"));
            $interest=$calc_interest[interest];

            $data4interest[rate]=$period_data['libor_rate'];//+$period_data['rate'];
            $calc_interest=$this->interest->getInterest($data4interest);
            $libor_interest=$calc_interest[interest];


            if($data[default_interest_bf]>0){
                $data4interest=[
                    'amount'=>$balance_prev,
                    'rate'=>$data['d_rate'],
                    'freq'=>$data['compounding_freq'],
                    'df'=>$period_data[df],
                    'dt'=>$period_data[dt],
                    'base'=>$data[base],
                    'compound'=>$data[compound],
                    'note'=>$period_data[note],
                ];
                $calc_interest=$this->interest->getInterest($data4interest);
                $def_interest=$calc_interest[interest];
            }

            $interest_amount=$interest;
            $modulus=($m) % $data[compounding_mod];
            $interest_balance+=$interest_amount+$def_interest;
            if (($data[compounding]>0)&&($modulus == 0)&&($period_data[note]=='maturity')){
                $balance=$balance+$interest_balance;
                $interest_balance=0;
            }
            $no_str="$i";
            $balance=$balance+$given-$returned;
            $interest_accumulated+=$interest_amount+$def_interest;
            
            $total_balance=$balance+$interest_balance;
            $class='';
            if($period_data[note]=='chk')$class='bold';
            $tbl.="<tr class='$class'><td>$no_str</td>
            <td>$period_data[note]</td>
            <td>$date</td>
            <td class='n'>$days</td>
            <td class='n'>".$this->html->money($given)."</td>
            <td class='n'>".$this->html->money($payment)."</td>
            <td class='n'>".$this->html->money($pricipal_paid)."</td>
            <td class='n'>".$this->html->money($interest_paid)."</td>
            <td class='n'>".$this->html->money($balance)."</td>
            <td class='n'>".$this->html->money($interest_amount)."</td>";
            if($data[default_interest_bf]>0){
                $tbl.="<td class='n'>".$this->html->money($def_interest)."</td>";
                $tbl.="<td class='n'>".$this->html->money($def_interest+$interest_amount)."</td>";
            }
            $tbl.="<td class='n'>".$this->html->money($interest_accumulated)."</td>
            <td class='n'>".$this->html->money($interest_balance)."</td>
            <td class='n'>".$this->html->money($total_balance)."</td>";
                   
            if($data[maturity_id]>0){
                $tbl.="<td class='n'>".$this->html->money($rate*100,'','',5)." %</td>";
                $tbl.="<td class='n'>".$this->html->money($margin_rate*100,'','',5)."</td>";
                $tbl.="<td class='n'>".$this->html->money($libor_interest)."</td>";
                $tbl.="<td class='n'>".$this->html->money($period_data['libor_rate']*100,'','',5)." %</td>";
                $tbl.="<td>$period_data[libor_date]</td>";
            }else{
                $tbl.="<td class='n'>".$this->html->money($rate*100,'','',5)." %</td>";
            }

            if($data[default_interest_bf]>0){
                $tbl.="<td class='n'>".$this->html->money($data['d_rate']*100,'','',5)." %</td>";
            }

            $tbl.="<td>$note</td>";
            //$tbl.="<td class='n'>G:".$this->html->money($t_given)." A:".$this->html->money($t_amount)." p:".$this->html->money($t_paid)." I:".$this->html->money($t_interest_paid)." ".$this->html->money($t_interest_paid)."</td>";
            $tbl.="</tr>";

            $plan[]=[
                'no'=>$no_str,
                'action'=>$period_data[note],
                'date'=>$date,
                'df'=>$period_data[df],
                'dt'=>$period_data[dt],
                'given'=>$given,
                'returned'=>$amount,
                'int_paid'=>$interest_paid,
                'balance'=>$balance,
                'interest'=>$interest_amount,
                'interest_accumulated'=>$interest_accumulated,
                'interest_balance'=>$interest_balance,
                'total_balance'=>$total_balance,
                'rate'=>$period_data['rate'],
                'days'=>$days,
                'libor_interest'=>$libor_interest,
                'libor_rate'=>$period_data['libor_rate'],
                'libor_date'=>$period_data[libor_date],
                't_given'=>$data[amount],
                't_returned'=>$t_principal_paid,
                't_interest'=>$t_interest_paid,
                't_paid'=>$t_interest_paid+$t_principal_paid,
                'info'=>'',
            ];

            if($period_data[note]=='chk'){
                $data['balance_principal']=$data[amount]-$principal_paid_total;
                $data['balance_interest']=$total_balance-($data[amount]-$t_principal_paid);
                $data['balance_total']=$total_balance;

                if(false){
                    $interest_accumulated-=$interest_amount;
                    $interest_balance-=$interest_amount;
                    $total_libor_interest-=$libor_interest;
                    $plain_interest-=$interest_amount;
                }
                


                // $t_interest-=$interest_amount;

            }

            $balance_prev=$balance;
            $t_given+=$given;
            $t_returned+=$returned;
            $total_libor_interest+=$libor_interest;
            $plain_interest+=$interest_amount;

            if($period_data[note]=='chk')break;
        }

        $totals=array_fill(0, 20, 0);
        $totals[2]=$t_days;
        $totals[3]=$t_given;
        $totals[4]=$t_returned;
        $totals[7]=$balance;
        $totals[8]=$plain_interest;
        $totals[9]=$interest_accumulated;
        $totals[10]=$interest_balance;
        $totals[11]=$total_balance;
        if($data[maturity_id]>0){
            if($data[default_interest_bf]>0){
                $totals[16]=$total_libor_interest;
            }else{
                $totals[14]=$total_libor_interest;
            }
        }
        

        $tbl.=$this->html->tablefoot($i, $totals, $no);
        $res=$data;
        $res[period_data]=$period_data_arr2;
        $res[tbl]=$tbl;
        $res[plan]=$plan;
        //echo $this->html->pre_display($res,"res");
        return $res;
    }
    public function getPlan($data)
    {
        //echo $this->html->pre_display($data,"f:getPlan");
        $period_data_arr=$data[period_data];
        $payment_range=$data[payment_range];
        $balance=$data[amount];
        $pmt_amount=$data[pmt];
        $payments=$data['payments'];
        $periods=$data['periods'];
        $t_init_interest=$data['interest_bf'];
        //$t_interest_compound=$data['interest_bf'];
        //$t_interest_compound_accum=$data['interest_bf'];
        $payments=$data['payments'];
        $payment_range=round($periods/$payments);
        //echo "payment_range=$payment_range=round($periods/$payments)<br>";

        if (($payment_range==0)||($payment_range==INF)) {
            $payment_range=1;
        }
        //echo "pmt_amount=$pmt_amount=round($data[amount]/$payments, 2)<br>";
        if (($payment_range>=1)&&($pmt_amount==0)&&($payments>0)) {
            $pmt_amount=round($data[amount]/$payments, 2);
        }

        //echo $this->html->pre_display($payment_range,"payment_range");
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
        if($data[maturity_id]>0){
            $fields=array('#','Action','date','Given','Payment','Pcpl. paid','Int.paid','Balance','Interest','Int. Accum.','Interest Bal','Tot.Bal.','Int. rate total','Def.Interest','Def.rate','Days','Margin rate','Floating int.','Floating rate','Rate date','');
            }else{
                $fields=array('#','Action','date','Given','Payment','Pcpl. paid','int.Paid','Balance','Interest','rate','def.Interest','Def.rate','days','');
            }
        $tbl=$this->html->tablehead($what, $qry, $order, $addbutton, $fields, 'no_sort');
        foreach ($period_data_arr as $key => $period_data) {
            $i++;
            //echo "$period_data[df] $period_data[dt] $period_data[note]<br>";
            $date=$period_data[dt];
            $given=($period_data[note]=='loan')?$data[amount]:0;
            $data4interest=[
                'amount'=>$balance,
                'rate'=>$period_data['rate'],
                'freq'=>$data['compounding_freq'],
                'df'=>$period_data[df],
                'dt'=>$period_data[dt],
                'base'=>$data[base],
                'compound'=>$data[compound],
                'note'=>$period_data[note],
            ];
            $calc_interest=$this->interest->getInterest($data4interest);
            // echo $this->html->cols2($this->html->pre_display($data4interest,"data4interest $period_data[df] $period_data[dt] $period_data[note]"),
            //     $this->html->pre_display($calc_interest,"calc_interest $period_data[df] $period_data[dt] $period_data[note]")
            //     );
            $interest=$calc_interest[interest];

            $data4interest[rate]=$period_data['libor_rate'];//+$period_data['rate'];
            $calc_interest=$this->interest->getInterest($data4interest);
            $libor_interest=$calc_interest[interest];

            if(!($data[int_paid_last]>0))$interest_paid=$interest;
            
            if(($period_data[note]!='chk')&&($period_data[note]!='loan')){
                if($period_data[note]!='loan'){
                    $no++;
                    $no_str=$no;
                }else{
                    $no_str='';
                }
                $pmt=(($no%$payment_range)==0)?$pmt_amount:0;
                //echo "I.$no=".($no%$payment_range)." ($pmt)($pmt_amount)($payment_range)<br>";
                if (($pmt==0)&&($no==$periods)) {
                    $pmt=$balance+$interest;
                }
                if ($pmt>0) {
                    $amount=$pmt-$interest;
                    $interest_paid=$interest;
                    if(($data[int_paid_last]>0))$interest_paid=$t_interest+$interest;
                } else {
                    $amount=0;
                }
                if (($payment_range>1)&&($pmt>0)) {
                    $amount=$pmt;
                }
                $days=$period_data[days];//$this->dates->F_datediff($date_prev, $date, $data[base]);
                $t_days+=$days;
            }else{
                //echo "note:$period_data[note]<br>";
                $total=0;
                $amount=0;
                $no_str='';
                $interest_paid=0;
                if($period_data[note]=='chk'){
                    $days=$period_data[days];
                    $i--;
                }else{
                    $interest=0;
                    $libor_interest=0;
                }
            }

            $t_given+=$given;
            $t_paid+=$pmt;
            $t_interest_paid+=$interest_paid;
            $t_interest+=$interest;
            $t_interest_compound_accum+=$interest;
            //$data['interest_bf']=0;
            if ((($i-1) % $data[compounding_mod] == 0)&&($period_data[note]!='chk')){
                $t_interest_compound+=$t_interest_compound_accum;
                $t_interest_compound_accum=0;
            }

            $t_principal_paid+=$amount;
            $total=$amount+$interest_paid;
            $t_total_paid+=$total;

            $t_amount+=$amount;

            $t_default_interest+=$default_interest;
            $t_libor_interest+=$libor_interest;
            //$t_margin_interest+=$margin_interest;

            $balance_prev=$balance;
            if ($this->dates->is_earlier($date, $data['dt'],1)) {
                $rate=$period_data['rate'];
                $def_rate=0;
                $init_interest=$interest;
                $def_interest=0;
            }else{
                $def_rate=$period_data['rate'];
                $rate=0;
                $def_interest=$interest;
                $init_interest=0;
            }
            if($period_data[note]!='chk'){
                if ($this->dates->is_earlier($date, $data['dt'])) {
                    if(($data[compound]>0)||($data[compounding]>0)){
                        //$balance=$balance-$amount+$interest-$interest_paid;
                        $balance=$t_given-$t_amount+$t_interest_compound-$t_interest_paid;
                    }else{
                        //$balance=$balance-$amount;
                        $balance=$t_given-$t_amount-$t_paid;
                    }
                }else{
                    if(($data[compound_default]>0)||($data[compounding_default]>0)){
                        //$balance=$balance-$amount+$interest-$interest_paid;
                        $balance=$t_given-$t_amount+$t_interest-$t_interest_paid;
                    }else{
                        $balance=$balance-$amount;
                    }
                }
            }else{
                // $t_interest_compound_accum+=$init_interest;
            }
            
            $t_init_interest+=$init_interest;
            $t_def_interest+=$def_interest;
            $t_bal=$balance+$t_interest_compound_accum;
            //echo \util::pre_display($period_data,"period_data");
            $tbl.="<tr class='$class'><td>$no_str</td>
            <td>$period_data[note]</td>
            <td>$date</td>
            <td class='n'>".$this->html->money($given)."</td>
            <td class='n'>".$this->html->money($total)."</td>
            <td class='n'>".$this->html->money($amount)."</td>
            <td class='n'>".$this->html->money($interest_paid)."</td>
            <td class='n'>".$this->html->money($balance)."</td>
            <td class='n'>".$this->html->money($init_interest)."</td>
            <td class='n'>".$this->html->money($t_init_interest)."</td>
            <td class='n'>".$this->html->money($t_interest_compound_accum)."</td>
            <td class='n'>".$this->html->money($t_bal)."</td>
            <td class='n'>".$this->html->money($rate*100,'','',5)." %</td>
            <td class='n'>".$this->html->money($def_interest)."</td>
            <td class='n'>".$this->html->money($def_rate*100,'','',5)." %</td>
            <td class='n'>$days</td>";
            if($data[maturity_id]>0){
            $tbl.="<td class='n'>".$this->html->money($margin_rate*100,'','',5)."</td>";
            $tbl.="<td class='n'>".$this->html->money($libor_interest)."</td>";
            $tbl.="<td class='n'>".$this->html->money($period_data['libor_rate']*100,'','',5)." %</td>";
            $tbl.="<td>$period_data[libor_date]</td>";
            }
            $tbl.="<td>$note</td>";
            //$tbl.="<td class='n'>G:".$this->html->money($t_given)." A:".$this->html->money($t_amount)." p:".$this->html->money($t_paid)." I:".$this->html->money($t_interest_paid)." ".$this->html->money($t_interest_paid)."</td>";
            $tbl.="</tr>";
            $plan[]=[
                'no'=>$no_str,
                'action'=>$period_data[note],
                'date'=>$date,
                'df'=>$period_data[df],
                'dt'=>$period_data[dt],
                'given'=>$given,
                'returned'=>$amount,
                'int_paid'=>$interest_paid,
                'balance'=>$balance,
                'interest'=>$interest,
                'interest_accum'=>$t_init_interest,
                'interest_bal'=>$t_interest_compound_accum,
                'total_bal'=>$t_bal,
                'rate'=>$period_data['rate'],
                'days'=>$days,
                'default_interest'=>$default_interest,
                'libor_interest'->$libor_interest,
                'libor_rate'=>$period_data['libor_rate'],
                'libor_date'=>$period_data[libor_date],
                't_given'=>$data[amount],
                't_returned'=>$t_principal_paid,
                't_interest'=>$t_interest_paid,
                't_paid'=>$t_interest_paid+$t_principal_paid,
                'info'=>'',
                ];
            if($period_data[note]=='chk'){
                $data['balance_principal']=$data[amount]-$t_principal_paid;
                $data['balance_interest']=$t_bal-($data[amount]-$t_principal_paid);
                $data['balance_total']=$t_bal;

                $t_interest_compound_accum-=$init_interest;
                $t_init_interest-=$init_interest;
                $t_interest-=$init_interest;

            }
        }
        $totals=array_fill(0, 20, 0);
        $totals[2]=$data[amount];
        $totals[3]=$t_total_paid;
        $totals[4]=$t_principal_paid;
        $totals[5]=$t_interest_paid;
        $totals[8]=$t_init_interest;
        $totals[9]=$t_interest_compound_accum;
        $totals[12]=$t_def_interest;
        //$totals[6]=$balance;
        $totals[14]=$t_days;
        if($data[maturity_id]>0){
            //$totals[12]=$t_default_interest;
            $totals[16]=$t_libor_interest;
            //$totals[12]=$t_margin_interest;
        }

        $tbl.=$this->html->tablefoot($i, $totals, $no);

        $res=$data;
        $res[period_data]=$period_data_arr2;
        $res[tbl]=$tbl;
        $res[plan]=$plan;
        //echo $this->html->pre_display($res,"res");
        return $res;
    }


    public function planLoan($data)
    {
        $GLOBALS[debug][stopwatch]='plan_loan';
        //echo $this->html->pre_display($data,"f:plan_loan");
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
        if ($data['align']>0) $periods++;
        $payments=$data['payments'];
        $payment_range=round($periods/$payments);
        //echo "payment_range=$payment_range=round($periods/$payments)<br>";

        if (($payment_range==0)||($payment_range==INF)) {
            $payment_range=1;
        }
        //echo $this->html->pre_display($payment_range,"payment_range");
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
        //echo $this->html->pre_display($data,"F:plan_loan");
        //echo "D:$payment_range<br>";
        $fields=array('#','Action','date','Given','Payment','Pcpl. paid','int.Paid','Balance','Interest','rate','days','');
        $tbl=$this->html->tablehead($what, $qry, $order, $addbutton, $fields, $sort);
        $i=0;
        $no=1;
        $date=$data[df];
        $pmt_amount=$data[pmt];
        if (($payment_range>=1)&&($pmt_amount==0)&&($payments>0)) {
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
        $ignore_weekends=($data['ignore_weekends']=='t')?1:0;
        $date_initial=$date;

        /// ========  Align to date
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
            $date=$this->dates->F_dateadd_day($date, $days_add,$ignore_weekends);
            $data4interest=array(
            'amount'=>$balance,
            'rate'=>$data['rate'],
            'freq'=>$data['freq'],
            'df'=>$date_prev,
            'dt'=>$date,
            'base'=>$data[base],
            'compound'=>$data[compound],
            'interest_margin'=>$data[interest_margin],
            'maturity_id'=>$data[maturity_id],
            'source_id'=>$data[source_id],
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
            //$notes=$this->html->pre_display($calc_interest);
            if (($days_chk<=0)&&($found==0)) {
                $found=1;
                $res[balance]=$balance_prev;
                $res[interest]=$interest;
                $res[t_paid]=$t_paid;
                $res[t_interest_paid]=$t_interest_paid;
                $res[t_principal_paid]=$t_principal_paid;
                $res[next_payment]=$date;
                $res[days_till_next]=-$days_chk;
                //$info=$this->html->pre_display($res);
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
        /// END ========  Align to date


        $date_alligned=$date;
    //if($pays_per_year>1)$months=12/$months_loan; else $months=12;
        if ($pays_per_year>1) {
            $months=12/$pays_per_year;
        } else {
            $months=12;
        }
        //echo "pays_per_year=$pays_per_year / $months_loan ($months)<br>";


        /// ========  Loop for periods
        for ($i=1; $i<=$periods; $i++) {
            $no++;
            $date_prev=$date;
            if ($months>1) {
                //echo "$months / $days_loan<br>";
                if ($pays_per_year>=1) {
                    $date_before=$date;
                    $date=$this->dates->F_dateadd_month($date_alligned, $months*$i,$ignore_weekends);
                    //echo $this->html->pre_display($pays_per_year,"$date_before - $date pays_per_year $months ($days_add)");
                } else {
                    $date=$this->dates->F_dateadd($date, $days_loan);
                    //echo "DL:$days_loan<br>";
                }
            
                if (($days_add>0)&&($data['align']==32)) {
                    $date=$this->dates->lastday_in_month($this->dates->F_dateadd($date, -15));
                }
                if (($days_add>0)&&($i==$periods)) {
                    $date=$this->dates->F_dateadd($date, -$days_add);
                }
                //if($no>=5)echo $this->html->pre_display(['Date'=>$date,'Days_add'=>$days_add],"result4");
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
            //$notes=$this->html->pre_display($calc_interest);
            if (($days_chk<=0)&&($found==0)) {
                $data4interest=array(
                'amount'=>$balance,
                'rate'=>$data['rate'],
                'freq'=>$data['freq'],
                'df'=>$date_prev,
                'dt'=>$data[date],
                'base'=>$data[base],
                'compound'=>$data[compound],
                'interest_margin'=>$data[interest_margin],
                'maturity_id'=>$data[maturity_id],
                'source_id'=>$data[source_id],
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
                //$info=$this->html->pre_display($res);
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
            'interest_margin'=>$data[interest_margin],
            'maturity_id'=>$data[maturity_id],
            'source_id'=>$data[source_id],
            'note'=>'Plan Loan. Final',
            );

            $calc_interest=$this->interest->getInterest($data4interest);
            //echo $this->html->pre_display($calc_interest,"calc_interest");
            $interest=$calc_interest[interest];
            $pmt=(($i%$payment_range)==0)?$pmt_amount:0;
            //echo "I.$i=".($i%$payment_range)." ($pmt)($pmt_amount)($payment_range)<br>";
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

        /// END ========  Loop for periods


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
