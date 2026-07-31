<?php

session_start();

include("../config/db.php");


if(isset($_POST['subject_name'])){


    // Get form data

    $subject_name = $_POST['subject_name'];
    $course = $_POST['course'];
    $day_name = $_POST['day_name'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $room = $_POST['room'];
    $semester = $_POST['semester'];
    $academic_year = $_POST['academic_year'];



    try {


        // ===============================
        // CHECK ROOM TIME CONFLICT
        // ===============================


        $check_sql = "
        SELECT COUNT(*) 
        FROM timetables

        WHERE

        day_name = :day_name

        AND

        room = :room

        AND

        (
            :start_time < end_time

            AND

            :end_time > start_time
        )
        ";



        $check_stmt = $pdo->prepare($check_sql);


        $check_stmt->execute([

            ':day_name' => $day_name,

            ':room' => $room,

            ':start_time' => $start_time,

            ':end_time' => $end_time

        ]);



        $conflict = $check_stmt->fetchColumn();



        if($conflict > 0)
        {


            
           echo "
<script>
alert('Timetable Added Successfully');
window.location='non_academic_staff/timetable_management.php';
</script>
";
            ";


            exit();

        }




        // ===============================
        // INSERT TIMETABLE
        // ===============================


        

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


            ':subject_name' => $subject_name,

            ':course' => $course,

            ':day_name' => $day_name,

            ':start_time' => $start_time,

            ':end_time' => $end_time,

            ':room' => $room,

            ':semester' => $semester,

            ':academic_year' => $academic_year


        ]);




        // Success redirect

        header("Location: timetable_management.php");

        exit();



    }


    catch(PDOException $e)

    {


        echo "Database Error : " . $e->getMessage();


    }



}


else

{

    echo "No data received";

}


?>