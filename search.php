<?php 

require_once 'Tickets.php';

try{
	$tic = new Tickets($_POST['start'], $_POST['finish']);
	$route = $tic->startSearch();
}catch (Exception $e){
	$err = $e->getMessage();
}

if(isset($route)){
	echo "<p>Full route: </p>";
	
	$route[0] = '<strong>'.$route[0].'</strong>';
	$route[count($route) - 1] = '<strong>'.$route[count($route) - 1].'</strong>';
	
	foreach($route as $key=>$point){
		if($key != count($route)-1){
			echo $point.' >>> ';
		}else{
			echo $point;
		}
	}
	
	echo "<p>The price of the trip: </p>";
	echo $tic->getPrice();
	
}else{
	echo $err;
}

