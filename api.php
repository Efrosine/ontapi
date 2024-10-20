<?php
// Set header untuk JSON response dan CORS
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Koneksi ke database MySQL
$host = "localhost";
$db_name = "smart_home";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $db_name);

if ($conn->connect_error) {
    die(json_encode(array("message" => "Connection failed: " . $conn->connect_error)));
}

// Function untuk mengirim response JSON
function send_json_response($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data);
}

// Membaca input JSON dari request body
$input_data = json_decode(file_get_contents("php://input"), true);

// Method GET untuk mengambil data perangkat berdasarkan device_id dalam request body
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['device_id'])) { // Ambil device_id dari query parameter
        $device_id = $conn->real_escape_string($_GET['device_id']);
        $query = "SELECT * FROM devices WHERE id = '$device_id'";
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            send_json_response($row);
        } else {
            send_json_response(array("message" => "Device not found."), 404);
        }
    } else {
        // Jika tidak ada device_id, ambil semua data perangkat
        $query = "SELECT * FROM devices";
        $result = $conn->query($query);
        $devices = array();

        while ($row = $result->fetch_assoc()) {
            $devices[] = $row;
        }
        send_json_response($devices);
    }
}

// Method POST untuk menyimpan data perangkat atau meng-update status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi input
    if (isset($input_data['device_name']) && isset($input_data['status'])) {
        $device_name = $conn->real_escape_string($input_data['device_name']);
        $status = $conn->real_escape_string($input_data['status']);
        $temperature = isset($input_data['temperature']) ? floatval($input_data['temperature']) : null;

        // Insert data baru ke database
        $query = "INSERT INTO devices (device_name, status, temperature) VALUES ('$device_name', '$status', '$temperature')";

        if ($conn->query($query)) {
            send_json_response(array("message" => "Device data saved successfully."));
        } else {
            send_json_response(array("message" => "Error saving device data."), 500);
        }
    } else {
        send_json_response(array("message" => "Invalid input."), 400);
    }
}

// Tutup koneksi database
$conn->close();
