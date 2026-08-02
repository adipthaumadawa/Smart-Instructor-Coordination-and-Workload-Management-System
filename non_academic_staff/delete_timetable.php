<?php

session_start();

include("../config/db.php");

if (isset($_POST['subject_name'])) {

    $subject_name = $_POST['subject_name'];
    $course = $_POST['course'];
    $day_name = $_POST['day_name'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $room = $_POST['room'];
    $semester = $_POST['semester'];
    $academic_year = $_POST['academic_year'];

    try {

        // Check whether the timetable exists
        $check_sql = "SELECT id
                      FROM timetables
                      WHERE subject_name = :subject_name
                      AND course = :course
                      AND day_name = :day_name
                      AND start_time = :start_time
                      AND end_time = :end_time
                      AND room = :room
                      AND semester = :semester
                      AND academic_year = :academic_year";

        $check_stmt = $pdo->prepare($check_sql);

        $check_stmt->execute([
            ':subject_name' => $subject_name,
            ':course' => $course,
            ':day_name' => $day_name,
            ':start_time' => $start_time,
            ':end_time' => $end_time,
            ':room' => $room,
            ':semester' => $semester,
            ':academic_year' => $academic_year
        ]);

        if ($check_stmt->rowCount() == 0) {

            $_SESSION['error'] = "Timetable Slot Not Found!";
            header("Location: timetable_records.php");
            exit();

        }

        // Delete timetable
        $delete_sql = "DELETE FROM timetables
                       WHERE subject_name = :subject_name
                       AND course = :course
                       AND day_name = :day_name
                       AND start_time = :start_time
                       AND end_time = :end_time
                       AND room = :room
                       AND semester = :semester
                       AND academic_year = :academic_year";

        $delete_stmt = $pdo->prepare($delete_sql);

        $delete_stmt->execute([
            ':subject_name' => $subject_name,
            ':course' => $course,
            ':day_name' => $day_name,
            ':start_time' => $start_time,
            ':end_time' => $end_time,
            ':room' => $room,
            ':semester' => $semester,
            ':academic_year' => $academic_year
        ]);

        $_SESSION['success'] = "Timetable Deleted Successfully!";
        header("Location: timetable_records.php");
        exit();

    } catch (PDOException $e) {

        echo "Database Error: " . $e->getMessage();

    }

} else {

    echo "No data received";

}