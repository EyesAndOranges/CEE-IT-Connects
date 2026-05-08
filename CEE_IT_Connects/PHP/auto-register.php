<?php
require 'db.php';

if (($handle = fopen(__DIR__ . '/../Sources/students.csv', 'r')) !== FALSE) {
    $headers = fgetcsv($handle);

    $stmt = $pdo->prepare("
        INSERT INTO students (
            email,
            full_name,
            student_id,
            program,
            year_level,
            section,
            contact_number,
            password_hash
        )
        VALUES (
            :email,
            :full_name,
            :student_id,
            :program,
            :year_level,
            :section,
            :contact_number,
            :password_hash
        )
        ON CONFLICT (student_id) DO NOTHING
    ");

    while (($row = fgetcsv($handle, 1000, ',')) !== false) {

        $student = array_combine($headers, $row);

        $defaultPassword = password_hash($student['student_id'], PASSWORD_DEFAULT);

        $stmt->execute([
            'email' => trim($student['email']),
            'full_name' => trim($student['full_name']),
            'student_id' => trim($student['student_id']),
            'program' => trim($student['program']),
            'year_level' => (int) $student['year_level'],
            'section' => trim($student['section']),
            'contact_number' => trim($student['contact_number']),
            'password_hash' => $defaultPassword
        ]);
    }
    fclose($handle);


    echo "Students imported successfully.";
    header("Location: internship-ui.php?imported=1");
    exit;
} else {
    echo "Error opening the file.";
}
?>