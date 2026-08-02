<?php

session_start();

include("../config/db.php");


if(isset($_POST['semester']) && isset($_POST['academic_year'])){

    $semester = $_POST['semester'];
    $academic_year = $_POST['academic_year'];


    $sql = "
    SELECT *
    FROM timetables
    WHERE semester = :semester
    AND academic_year = :academic_year
    ORDER BY start_time
    ";


    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':semester'=>$semester,
        ':academic_year'=>$academic_year
    ]);


    $records = [];


    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){

        $records[$row['day_name']][$row['start_time']] = $row;

    }



}
else{

    echo "No timetable selected";
    exit();

}



$times = [

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


$days = [

"Monday",
"Tuesday",
"Wednesday",
"Thursday",
"Friday"

];

?>


<!DOCTYPE html>

<html>

<head>

<title>View Timetable</title>


<style>


body{

font-family:Arial,sans-serif;

}



.container{

width:95%;
margin:auto;

}



.header{

text-align:center;

font-weight:bold;

margin-bottom:20px;

}



.header h2{

margin:5px;

}


.info{

text-align:center;
font-size:18px;
margin-bottom:20px;

}



table{

width:100%;
border-collapse:collapse;

}



th,td{

border:1px solid black;
text-align:center;

height:55px;

}



th{

background:#eeeeee;

}



.time{

font-weight:bold;
width:120px;

}



.subject{

font-size:13px;
font-weight:bold;

}



.room{

font-size:12px;

}



.lunch{

font-weight:bold;
font-size:18px;

}



button{

margin-top:20px;
padding:10px 25px;
font-size:16px;
cursor:pointer;

}



@media print{

button{

display:none;

}

}



</style>


</head>


<body>


<div class="container">


<div class="header">


<h2>
University of Colombo School of Computing (UCSC)
</h2>


<p>
Bachelor of Science in Computer Science and Bachelor of Science in Information Systems Degree Programme
</p>


<h3>
Lecture Time Table - 
<?php echo "Year ".$academic_year." - ".$semester; ?>
</h3>


</div>



<div class="info">


Semester : <?php echo $semester; ?>

<br>

Academic Year : <?php echo $academic_year; ?>


</div>



<table>


<tr>

<th class="time">
TIME
</th>


<?php foreach($days as $day){ ?>

<th colspan="2">

<?php echo strtoupper($day); ?>

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




<?php foreach($times as $time=>$label){ ?>


<?php if($time=="13:00:00"){ ?>


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

<?php echo $label; ?>

</td>



<?php foreach($days as $day){ ?>


<?php


$is_class=false;

$cs_class=false;



if(isset($records[$day][$time])){


$data=$records[$day][$time];


if($data['course']=="IS"){

$is_class=true;

}


if($data['course']=="CS"){

$cs_class=true;

}


}



?>



<td>


<?php if($is_class){ ?>

<div class="subject">

<?php echo $data['subject_name']; ?>

</div>


<div class="room">

<?php echo $data['room']; ?>

</div>


<?php } ?>


</td>



<td>


<?php if($cs_class){ ?>

<div class="subject">

<?php echo $data['subject_name']; ?>

</div>


<div class="room">

<?php echo $data['room']; ?>

</div>


<?php } ?>


</td>



<?php } ?>



</tr>



<?php } ?>



</table>


<center>

<button onclick="window.print()">

Print Timetable

</button>


</center>



</div>


</body>

</html>