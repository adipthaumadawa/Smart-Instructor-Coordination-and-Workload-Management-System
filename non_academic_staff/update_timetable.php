<?php

session_start();

include("../config/db.php");

if (isset($_POST['old_day'])) {

    // OLD SLOT
    $old_day = $_POST['old_day'];
    $old_start = $_POST['old_start'];
    $old_end = $_POST['old_end'];
    $old_room = $_POST['old_room'];

    // NEW SLOT
    $new_subject = $_POST['new_subject'];
    $new_course = $_POST['new_course'];
    $new_day = $_POST['new_day'];
    $new_start = $_POST['new_start'];
    $new_end = $_POST['new_end'];
    $new_room = $_POST['new_room'];
    $new_semester = $_POST['new_semester'];
    $new_year = $_POST['new_year'];

    try {

        // Check old timetable
        $check = $pdo->prepare("
            SELECT id
            FROM timetables
            WHERE day_name = :old_day
            AND start_time = :old_start
            AND end_time = :old_end
            AND room = :old_room
        ");

        $check->execute([
            ':old_day' => $old_day,
            ':old_start' => $old_start,
            ':old_end' => $old_end,
            ':old_room' => $old_room
        ]);

        if ($check->rowCount() == 0) {

            $_SESSION['error'] = "Old Timetable Slot Not Found!";
            header("Location: timetable_records.php");
            exit();

        }

        // Check room conflict
        $conflict = $pdo->prepare("
            SELECT COUNT(*)
            FROM timetables
            WHERE day_name = :new_day
            AND room = :new_room
            AND (
                    :new_start < end_time
                    AND
                    :new_end > start_time
                )
            AND NOT(
                    day_name = :old_day
                    AND start_time = :old_start
                    AND end_time = :old_end
                    AND room = :old_room
                )
        ");

        $conflict->execute([

            ':new_day' => $new_day,
            ':new_room' => $new_room,
            ':new_start' => $new_start,
            ':new_end' => $new_end,

            ':old_day' => $old_day,
            ':old_start' => $old_start,
            ':old_end' => $old_end,
            ':old_room' => $old_room

        ]);

        if ($conflict->fetchColumn() > 0) {

            $_SESSION['error'] = "New Timetable Slot Already Exists!";
            header("Location: timetable_records.php");
            exit();

        }

        // Update timetable
        $update = $pdo->prepare("
            UPDATE timetables
            SET
                subject_name = :new_subject,
                course = :new_course,
                day_name = :new_day,
                start_time = :new_start,
                end_time = :new_end,
                room = :new_room,
                semester = :new_semester,
                academic_year = :new_year
            WHERE
                day_name = :old_day
                AND start_time = :old_start
                AND end_time = :old_end
                AND room = :old_room
        ");

        $update->execute([

            ':new_subject' => $new_subject,
            ':new_course' => $new_course,
            ':new_day' => $new_day,
            ':new_start' => $new_start,
            ':new_end' => $new_end,
            ':new_room' => $new_room,
            ':new_semester' => $new_semester,
            ':new_year' => $new_year,

            ':old_day' => $old_day,
            ':old_start' => $old_start,
            ':old_end' => $old_end,
            ':old_room' => $old_room

        ]);

        $_SESSION['success'] = "Timetable Updated Successfully!";
        header("Location: timetable_records.php");
        exit();

    } catch(PDOException $e){

        echo "Database Error : ".$e->getMessage();

    }

} else {

    echo "No data received";

}