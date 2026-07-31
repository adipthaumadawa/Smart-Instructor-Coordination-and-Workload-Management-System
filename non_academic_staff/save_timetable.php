<?php

session_start();

include("../config/db.php");


if(isset($_POST['subject_name'])){


    $subject_name = $_POST['subject_name'];
    $course = $_POST['course'];
    $day_name = $_POST['day_name'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $room = $_POST['room'];
    $semester = $_POST['semester'];
    $academic_year = $_POST['academic_year'];



    try {


        $sql = "INSERT INTO timetables
        (
            subject_name,
            course,
            day_name,
            start_time,
            end_time,
            room,
            semester,
            academic_year
        )

        VALUES

        (
            :subject_name,
            :course,
            :day_name,
            :start_time,
            :end_time,
            :room,
            :semester,
            :academic_year
        )";


        $stmt = $pdo->prepare($sql);


        $stmt->execute([

            ':subject_name'=>$subject_name,
            ':course'=>$course,
            ':day_name'=>$day_name,
            ':start_time'=>$start_time,
            ':end_time'=>$end_time,
            ':room'=>$room,
            ':semester'=>$semester,
            ':academic_year'=>$academic_year

        ]);



        echo "
        <script>
        alert('Timetable Added Successfully');
        window.location='timetable_management.php';
        </script>
        ";



    } catch(PDOException $e){

        echo "Error: ".$e->getMessage();

    }


}

else{

    echo "No data received";

}

?>