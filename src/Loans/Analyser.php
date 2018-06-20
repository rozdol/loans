<?php 
///===========================================


$data_plan=[];
$given=0;
$returned=0;
$int_paid=0;
$i=0;
$out.="<h3>Analysis results by plan</h3>";
$fields=array('#','Action','date','executed','should be','difference');
$out.= $this->tablehead($what,$qry, $order, $addbutton, $fields,$sort);
$sql="SELECT * FROM plan p where date_YMD<='".$this->F_USdate($GLOBALS[today])."'";
$plan2=$this->sql_to_array($sql);

//echo $this->html->pre_display($plan2,"plan2 ".$GLOBALS[today]);exit;

foreach($plan2 as $no=>$row){
	$i++;
	$data=[];
	$info=[];
	//echo "$i<br>";
	$row[given]=round($row[given],2);
	$row[returned]=round($row[returned],2);
	$row[int_paid]=round($row[int_paid],2);
	unset($row[id]);
	//echo $this->html->pre_display($row,"result");
	
	$sql="SELECT * FROM calc p where date_YMD='$row[date_YMD]'";
	$transactions=$this->sql_to_array($sql);
	$transaction=$transaction[0];
	unset($transaction_tmp);
	foreach ($transactions as $key => $transaction_loop) {
		$transaction_tmp[given]+=round($transaction_loop[given],2);
		$transaction_tmp[returned]+=round($transaction_loop[returned],2);
		$transaction_tmp[int_paid]+=round($transaction_loop[int_paid],2);
	}
	$transaction[given]=round($transaction_tmp[given],2);
	$transaction[returned]=round($transaction_tmp[returned],2);
	$transaction[int_paid]=round($transaction_tmp[int_paid],2);

	unset($transaction[id]);
	
	//$out.= $this->html->pre_display($transaction,"plan $row[no]");
	//echo $this->html->cols2($this->html->pre_display($row),$this->html->pre_display($transaction),"Plan","Real");
	

	$alerted=0;
	if(($row[given]!=$transaction[given])&&($row[given]>0)){
		$given=$given+$row[given]-$transaction[given];
		$diff=$transaction[given]-$row[given];
		$out.= "<tr><td>$row[no]</td><td>Wrong <b>amount</b> borrowed</td><td>$row[date]</td><td class='n'><span class='badge red'>".$this->money($transaction[given])."</span></td><td class='n'><span class='badge green'> ".$this->money($row[given])."</span></td><td class='n'><span class='badge red'>".$this->money($diff)."</span></td></tr>";
		$alerted=1;
		$info[message]="Wrong amount borrowed";
		$info[executed]=$transaction[given];
		$info[shoulbe]=$row[given];
		$info[diff]=$diff;

	}	
	if(($row[returned]!=$transaction[returned])&&($row[returned]>0)){
		$returned=$returned+$row[returned]-$transaction[returned];
		$diff=$transaction[returned]-$row[returned];
		$out.= "<tr><td>$row[no]</td><td>Wrong <b>principal</b> returned</td><td>$row[date]</td><td class='n'><span class='badge red'>".$this->money($transaction[returned])."</span></td><td class='n'><span class='badge green'>".$this->money($row[returned])."</span></td><td class='n'><span class='badge red'>".$this->money($diff)."</span></td></tr>";
		$alerted=1;
		$info[message]="Wrong principal returned";
		$info[executed]=$transaction[returned];
		$info[shoulbe]=$row[returned];
		$info[diff]=$diff;
	}
	if(($row[int_paid]!=$transaction[int_paid])&&($row[int_paid]>0)){
		$int_paid=$int_paid+$row[int_paid]-$transaction[int_paid];
		$diff=$transaction[int_paid]-$row[int_paid];
		$out.= "<tr><td>$row[no]</td><td>Wrong <b>interest</b> paid</td><td>$row[date]</td><td class='n'	><span class='badge red'>".$this->money($transaction[int_paid])."</span></td><td class='n'><span class='badge green'>".$this->money($row[int_paid])."</span></td><td class='n'><span class='badge red'>".$this->money($diff)."</span></td></tr>";
		$alerted=1;
		$info[message]="Wrong interest paid";
		$info[executed]=$transaction[int_paid];
		$info[shoulbe]=$row[int_paid];
		$info[diff]=$diff;
	}
	
	if(($int_paid!=0)&&($alerted==0)){
		$out.= "<tr><td>$row[no]</td><td>Wrong <b>interest</b> balance</td><td>$row[date]</td><td> </td><td> </td><td class='n'><span class='badge red'>".$this->money($int_paid)."</span></td></tr>";
		$info[message]="Wrong interest balance";
		$info[executed]=$int_paid;
	}
	if(($returned!=0)&&($alerted==0)){
		$out.= "<tr><td>$row[no]</td><td>Wrong <b>principal</b> balance</td><td>$row[date]</td><td> </td><td> </td><td class='n'><span class='badge red'>".$this->money($returned)."</span></td></tr>";
		$info[message]="Wrong principal balance";
		$info[executed]=$returned;
	}
	$data[info]=$info;
	$data[plan]=$row;
	$data[trans]=$transaction;
	$data_plan[]=$data;

	//if($returned!=0)
	//if($given!=0)
	//if(($given!=0)||($returned!=0)||($int_paid!=0))$out.= "$row[no] - $row[date] (G:$given;R:$returned;I:$int_paid) $row[given]=".$transaction[given]." | $row[returned]=".$transaction[returned]." | $row[int_paid]=".$transaction[int_paid]."<br>";
}
$out.= "<table>";

///===========================================
$data_trans=[];

$given=0;
$returned=0;
$int_paid=0;
$i=0;
$rows=count($calc[data]);
$out.="<h3>Analysis results by transactions</h3>";
$fields=array('#','Action','date','executed','should be','difference');
$out.= $this->tablehead($what,$qry, $order, $addbutton, $fields,$sort);
$sql="SELECT * FROM calc p";
$plan3=$this->sql_to_array($sql);
//echo $this->pre_display($plan3,"plan3");
foreach($plan3 as $no=>$transaction){
	$i++;
	$data=[];
	$info=[];
	//echo "$i<br>";
	//echo $this->pre_display($transaction,"result");
	
	$sql="SELECT * FROM plan p where date='$transaction[date]'";
	$plan=$this->sql_to_array($sql);
	$plan=$plan[0];
	unset($plan[id]);
	//$out.= $this->html->pre_display($plan,"plan $transaction[no]");
	//echo $this->html->cols2($this->html->pre_display($plan),$this->html->pre_display($transaction),"Plan","Real");
	$transaction[given]=round($transaction[given],2);
	$transaction[returned]=round($transaction[returned],2);
	$transaction[int_paid]=round($transaction[int_paid],2);
	
	$plan[given]=round($plan[given],2);
	$plan[returned]=round($plan[returned],2);
	$plan[int_paid]=round($plan[int_paid],2);
	$alerted=0;
	if(($transaction[given]!=$plan[given])&&($transaction[given]>0)){
		$given=$given+$transaction[given]-$plan[given];
		$diff=$transaction[given]-$plan[given];
		$out.= "<tr><td>$transaction[no]</td><td>Wrong <b>amount</b> borrowed</td><td>$transaction[date]</td><td class='n'><span class='badge red'>".$this->money($transaction[given])."</span></td><td class='n'><span class='badge green'> ".$this->money($plan[given])."</span></td><td class='n'><span class='badge red'>".$this->money($diff)."</span></td></tr>";
		$alerted=1;
		$info[message]="Wrong amount borrowed";
		$info[executed]=$transaction[given];
		$info[shoulbe]=$plan[given];
		$info[diff]=$diff;
	}	
	if(($transaction[returned]!=$plan[returned])&&($transaction[returned]>0)){
		$returned=$returned+$transaction[returned]-$plan[returned];
		$diff=$transaction[returned]-$plan[returned];
		$out.= "<tr><td>$transaction[no]</td><td>Wrong <b>principal</b> returned</td><td>$transaction[date]</td><td class='n'><span class='badge red'>".$this->money($transaction[returned])."</span></td><td class='n'><span class='badge green'>".$this->money($plan[returned])."</span></td><td class='n'><span class='badge red'>".$this->money($diff)."</span></td></tr>";
		$alerted=1;
		$info[message]="Wrong principal returned";
		$info[executed]=$transaction[given];
		$info[shoulbe]=$plan[given];
		$info[diff]=$diff;
	}
	if(($transaction[int_paid]!=$plan[int_paid])&&($transaction[int_paid]>0)){
		$int_paid=$int_paid+$transaction[int_paid]-$plan[int_paid];
		$diff=$transaction[int_paid]-$plan[int_paid];
		$out.= "<tr><td>$transaction[no]</td><td>Wrong <b>interest</b> paid</td><td>$transaction[date]</td><td class='n'	><span class='badge red'>".$this->money($transaction[int_paid])."</span></td><td class='n'><span class='badge green'>".$this->money($plan[int_paid])."</span></td><td class='n'><span class='badge red'>".$this->money($diff)."</span></td></tr>";
		$alerted=1;
		$info[message]="Wrong interest paid";
		$info[executed]=$transaction[given];
		$info[shoulbe]=$plan[given];
		$info[diff]=$diff;
	}
	
	if(($int_paid!=0)&&($alerted==0)){
		$out.= "<tr><td>$transaction[no]</td><td>Wrong <b>interest</b> balance</td><td>$transaction[date]</td><td> </td><td> </td><td class='n'><span class='badge red'>".$this->money($int_paid)."</span></td></tr>";
		$info[message]="Wrong interest balance";
		$info[executed]=$int_paid;
	}
	if(($returned!=0)&&($alerted==0)){
		$out.= "<tr><td>$transaction[no]</td><td>Wrong <b>principal</b> balance</td><td>$transaction[date]</td><td> </td><td> </td><td class='n'><span class='badge red'>".$this->money($returned)."</span></td></tr>";
		$info[message]="Wrong principal balance";
		$info[executed]=$returned;
	}
	
	//if($returned!=0)
	//if($given!=0)
	//if(($given!=0)||($returned!=0)||($int_paid!=0))$out.= "$transaction[no] - $transaction[date] (G:$given;R:$returned;I:$int_paid) $transaction[given]=".$plan[given]." | $transaction[returned]=".$plan[returned]." | $transaction[int_paid]=".$plan[int_paid]."<br>";
	$data[info]=$info;
	$data[plan]=$row;
	$data[trans]=$transaction;
	$data_trans[]=$data;
}

$out.= "<table>";

///===========================================
$res[data_trans]=$data_trans;
$res[data_plan]=$data_plan;
$res[html]=$out;

return $res;