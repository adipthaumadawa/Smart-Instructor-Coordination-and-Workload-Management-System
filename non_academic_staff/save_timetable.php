<?php

session_start();

include("../config/db.php");


// Check form submit

if(isset($_POST['subject_name'])){


    // Get values from form

    $subject_name = $_POST['subject_name'];

    $course = $_POST['course'];

    $day_name = $_POST['day_name'];

    $start_time = $_POST['start_time'];

    $end_time = $_POST['end_time'];

    $room = $_POST['room'];

    $semester = $_POST['semester'];

    $academic_year = $_POST['academic_year'];



    // Insert Query

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
        '$subject_name',
        '$course',
        '$day_name',
        '$start_time',
        '$end_time',
        '$room',
        '$semester',
        '$academic_year'
    )";



    // Execute Query

    if(mysqli_query($conn,$sql))
    {

        echo "
        <script>
        alert('Timetable Added Successfully');
        window.location='timetable_management.php';
        </script>
        ";

    }
    else
    {

        echo "Database Error : ".mysqli_error($conn);

    }


}

else
{

    echo "No Data Received";

}


?>