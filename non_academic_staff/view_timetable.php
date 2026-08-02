<?php

session_start();

include("../config/db.php");


if(!isset($_POST['semester']) || !isset($_POST['academic_year'])){

    echo "No timetable selected";
    exit();

}


$semester = $_POST['semester'];
$academic_year = $_POST['academic_year'];



$sql = "
SELECT *
FROM timetables
WHERE semester = :semester
AND academic_year = :year
ORDER BY start_time
";


$stmt = $pdo->prepare($sql);


$stmt->execute([

    ':semester'=>$semester,
    ':year'=>$academic_year

]);



$records=[];


while($row=$stmt->fetch(PDO::FETCH_ASSOC)){


    $records[$row['day_name']][]=$row;


}




$days=[

"Monday",
"Tuesday",
"Wednesday",
"Thursday",
"Friday"

];



$slots=[

"08:00:00",
"09:00:00",
"10:00:00",
"11:00:00",
"13:00:00",
"14:00:00",
"15:00:00",
"16:00:00",
"17:00:00",
"18:00:00"

];



$labels=[

"08:00:00"=>"8 AM - 9 AM",
"09:00:00"=>"9 AM - 10 AM",
"10:00:00"=>"10 AM - 11 AM",
"11:00:00"=>"11 AM - 12 PM",
"13:00:00"=>"1 PM - 2 PM",
"14:00:00"=>"2 PM - 3 PM",
"15:00:00"=>"3 PM - 4 PM",
"16:00:00"=>"4 PM - 5 PM",
"17:00:00"=>"5 PM - 6 PM",
"18:00:00"=>"6 PM - 7 PM"

];



$used=[];


?>


<!DOCTYPE html>
<html>

<head>

<title>View Timetable</title>


<style>


body{

font-family:Arial;

}


table{

width:95%;
margin:auto;
border-collapse:collapse;

}


td,th{

border:1px solid black;
text-align:center;

height:55px;

}


th{

background:#eee;

}


.subject{

font-weight:bold;
font-size:13px;

}


.room{

font-size:12px;

}


.time{

font-weight:bold;
width:120px;

}


.lunch{

font-size:18px;
font-weight:bold;

}


button{

margin:20px;
padding:10px 25px;

}



</style>


</head>


<body>



<h2 style="text-align:center">

University of Colombo School of Computing (UCSC)

</h2>


<h3 style="text-align:center">

Lecture Time Table - Year <?php echo $academic_year; ?>

(<?php echo $semester;?>)

</h3>



<table>


<tr>

<th class="time">
TIME
</th>


<?php foreach($days as $day){ ?>

<th colspan="2">
<?php echo strtoupper($day);?>
</th>

<?php } ?>


</tr>


<tr>

<th></th>


<?php foreach($days as $day){ ?>

<th>IS</th>
<th>CS</th>


<?php } ?>


</tr>




<?php foreach($slots as $slot){ ?>


<?php if($slot=="13:00:00"){ ?>


<tr>

<td class="time">
12 PM - 1 PM
</td>


<td colspan="10" class="lunch">
Lunch Break
</td>


</tr>


<?php } ?>



<tr>


<td class="time">

<?php echo $labels[$slot];?>

</td>




<?php foreach($days as $day){ ?>



<?php


if(isset($used[$day][$slot])){

continue;

}



$found=[];


foreach($records[$day] ?? [] as $data){


if($data['start_time']==$slot){

$found=$data;
break;

}


}




if(!empty($found)){



$start=strtotime($found['start_time']);

$end=strtotime($found['end_time']);


$hours=($end-$start)/3600;



$rowspan=$hours;



for($i=0;$i<$hours;$i++){


$next=date(
"H:i:s",
strtotime("+".$i." hour",$start)
);


$used[$day][$next]=true;


}



?>



<td rowspan="<?php echo $rowspan;?>">


<div class="subject">

<?php echo $found['subject_name'];?>

</div>


<div class="room">

<?php echo $found['room'];?>

</div>


</td>



<?php


}

else{


?>


<td></td>


<?php


}



?>




<td></td>



<?php } ?>



</tr>



<?php } ?>



</table>



<center>

<button onclick="window.print()">

Print

</button>

</center>



</body>

</html>