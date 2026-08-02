<?php

session_start();

include("../config/db.php");

if (isset($_POST['day_name'])) {

    $day_name = $_POST['day_name'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $room = $_POST['room'];

    try {

        // Check if timetable exists
        $check = $pdo->prepare("
            SELECT id
            FROM timetables
            WHERE day_name = :day_name
            AND start_time = :start_time
            AND end_time = :end_time
            AND room = :room
        ");

        $check->execute([
            ':day_name' => $day_name,
            ':start_time' => $start_time,
            ':end_time' => $end_time,
            ':room' => $room
        ]);

        if ($check->rowCount() == 0) {

            $_SESSION['error'] = "Timetable Slot Not Found!";
            header("Location: timetable_records.php");
            exit();

        }

        // Delete timetable
        $delete = $pdo->prepare("
            DELETE FROM timetables
            WHERE day_name = :day_name
            AND start_time = :start_time
            AND end_time = :end_time
            AND room = :room
        ");

        $delete->execute([
            ':day_name' => $day_name,
            ':start_time' => $start_time,
            ':end_time' => $end_time,
            ':room' => $room
        ]);

        $_SESSION['success'] = "Timetable Deleted Successfully!";
        header("Location: timetable_records.php");
        exit();

    } catch(PDOException $e){

        echo "Database Error : ".$e->getMessage();

    }

} else {

    echo "No data received";

}