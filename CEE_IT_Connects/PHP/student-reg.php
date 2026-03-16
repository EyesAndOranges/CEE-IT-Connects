<?php
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = $_POST['full_name'];

    $prefix = $_POST['year_no'];
    $suffix = $_POST['id_no'];
    if (!preg_match('/^(20|21|22|23|24|25)$/', $prefix)) {
        die("Invalid student year.");
    }

    if (!preg_match('/^\d{4}$/', $suffix)) {
        die("Student ID must be exactly 4 digits.");
    }

    // Combine final student ID
    $student_id = $prefix . '-' . $suffix;

    $program = $_POST['program'];
    $year_level = $_POST['year_level'];
    $section = $_POST['section'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];

    // HASH PASSWORD (SECURE)
    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // UPLOAD
    $cor_path = null;
    $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];

    if(!in_array($_FILES['cor_upload']['type'], $allowed_types)){
        die("Only JPG, PNG, PDF allowed.");
    }
    if (!empty($_FILES['cor_upload']['name'])) {
        $target_dir = "../uploads/";
        
        $file_name = time() . "_" . basename($_FILES["cor_upload"]["name"]);
        $full_path = $target_dir . $file_name;

        if(move_uploaded_file($_FILES["cor_upload"]["tmp_name"], $full_path)) {
            $cor_path = $file_name; // Save only filename
        } else {
            die("Upload failed.");
        }
    }

    // INSERT INTO DATABASE
    $stmt = $pdo->prepare("
        INSERT INTO students
        (full_name, student_id, program, year_level, section, cor_file, password_hash, contact_number, email)
        VALUES
        (:full_name, :student_id, :program, :year_level, :section, :cor_file, :password_hash, :contact_number, :email)
    ");

    $stmt->execute([
        ':full_name' => $full_name,
        ':student_id' => $student_id,
        ':program' => $program,
        ':year_level' => $year_level,
        ':section' => $section,
        ':cor_file' => $cor_path,
        ':password_hash' => $password_hash,
        ':contact_number' => $contact_number,
        ':email' => $email
    ]);

    // Redirect after success
    header("Location: ../PHP/student-welcome.php");
    exit();
}
?>
