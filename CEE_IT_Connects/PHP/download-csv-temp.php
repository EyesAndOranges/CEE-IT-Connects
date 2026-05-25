<?php
$type = $_GET['type'] ?? 'default';

$templates = [
    'student_room' => [
        'filename' => 'student_import_template.csv',
        'content' => "student_id\n"
    ],
    'student_register' => [
        'filename' => 'student_register_template.csv',
        'content' => "student_id,full_name,email,program,year_level,section,contact_number\n"
    ],
];

if (!isset($templates[$type])) {
    http_response_code(404);
    die("Template not found.");
}

$template = $templates[$type];

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $template['filename'] . '"');

echo $template['content'];