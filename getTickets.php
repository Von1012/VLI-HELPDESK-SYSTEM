<?php
include '../init.php';
header('Content-Type: application/json');

$data = [];
$sql = "SELECT t.id, t.title, t.department, d.name as dept_name
        FROM hd_tickets t
        JOIN hd_departments d ON t.department = d.id";
$result = $database->query($sql);
while($row = $result->fetch_assoc()){
    $data[] = [
        'id' => $row['id'],
        'department' => $row['dept_name'],
        'issue' => $row['title'],
        'count' => 1
    ];
}
echo json_encode($data);
