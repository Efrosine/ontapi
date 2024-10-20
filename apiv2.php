<?php
// Set header untuk JSON response dan CORS
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Koneksi ke database MySQL
$host = "localhost";
$db_name = "ontapi_service1";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $db_name);

if ($conn->connect_error) {
    die(json_encode(array("message" => "Connection failed: " . $conn->connect_error)));
}

// Function untuk mengirim response JSON
function send_json_response($data, $status_code = 200)
{
    http_response_code($status_code);
    echo json_encode($data);
}

// Membaca input JSON dari request body
$input_data = json_decode(file_get_contents("php://input"), true);

// **POST /api/features** - Menambah Fitur Baru dengan tipe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/api/features') !== false) {
    if (isset($input_data['service']) && isset($input_data['tipe'])) {
        $service = $conn->real_escape_string($input_data['service']);
        $tipe = $conn->real_escape_string($input_data['tipe']);

        // Pastikan tipe valid (sender, trigger, switch)
        $valid_tipes = array("sender", "trigger", "switch");
        if (!in_array($tipe, $valid_tipes)) {
            send_json_response(array("message" => "Invalid type. Must be one of 'sender', 'trigger', or 'switch'."), 400);
            exit();
        }



        $query = "INSERT INTO service (service, tipe) VALUES ('$service', '$tipe')";

        if ($conn->query($query)) {
            send_json_response(array("message" => "Service added successfully."), 201);
        } else {
            send_json_response(array("message" => "Error adding service."), 500);
        }
    } else {
        send_json_response(array("message" => "Invalid input. 'service' and 'tipe' are required."), 400);
    }
}

// **GET /api/sync** - Mengirim semua data dari kolom id, service, tipe, dan command ke ESP
if ($_SERVER['REQUEST_METHOD'] === 'GET' && strpos($_SERVER['REQUEST_URI'], '/api/sync') !== false) {
    $query = "SELECT id, service, tipe, command FROM service";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $services = array();
        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
        send_json_response($services);
    } else {
        send_json_response(array("message" => "No services found."), 404);
    }
}

// **PUT /api/features/{id}/value** - Memperbarui value berdasarkan ID service
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && strpos($_SERVER['REQUEST_URI'], '/api/features/') !== false && strpos($_SERVER['REQUEST_URI'], '/value') !== false) {

    // Gunakan preg_match untuk mendapatkan ID dari URI
    if (preg_match("/\/api\/features\/(\d+)\/value/", $_SERVER['REQUEST_URI'], $matches)) {
        $id = $matches[1];

        if (isset($input_data['value'])) {
            $value = $conn->real_escape_string($input_data['value']);

            // Validasi opsional: pastikan value sesuai dengan format tertentu, misalnya hanya angka
            if (is_numeric($value)) {  // Jika hanya menerima angka
                $query = "UPDATE service SET value = '$value' WHERE id = '$id'";

                if ($conn->query($query)) {
                    send_json_response(array("message" => "Value updated successfully."));
                } else {
                    send_json_response(array("message" => "Error updating value: " . $conn->error), 500);  // Tambahkan error MySQL untuk debug
                }
            } else {
                send_json_response(array("message" => "Invalid value format. Value must be numeric."), 400);
            }

        } else {
            send_json_response(array("message" => "Value is required."), 400);
        }
    } else {
        send_json_response(array("message" => "Invalid ID format."), 400);
    }
}


// **GET /api/commander/sync** - Mengirim semua data dari kolom id, service, tipe, dan value ke Commander
if ($_SERVER['REQUEST_METHOD'] === 'GET' && strpos($_SERVER['REQUEST_URI'], '/api/commander/sync') !== false) {
    $query = "SELECT id, service, tipe, value FROM service";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $services = array();
        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
        send_json_response($services);
    } else {
        send_json_response(array("message" => "No services found."), 404);
    }
}

// **PUT /api/features/{id}/command** - Memperbarui command berdasarkan ID service
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && strpos($_SERVER['REQUEST_URI'], '/api/features/') !== false && strpos($_SERVER['REQUEST_URI'], '/command') !== false) {

    // Gunakan preg_match untuk mendapatkan ID dari URI
    if (preg_match("/\/api\/features\/(\d+)\/command/", $_SERVER['REQUEST_URI'], $matches)) {
        $id = $matches[1];

        if (isset($input_data['command'])) {
            $command = $conn->real_escape_string($input_data['command']);

            $query = "UPDATE service SET command = '$command' WHERE id = '$id'";

            if ($conn->query($query)) {
                send_json_response(array("message" => "Command updated successfully."));
            } else {
                send_json_response(array("message" => "Error updating command: " . $conn->error), 500);
            }
        } else {
            send_json_response(array("message" => "Command is required."), 400);
        }
    } else {
        send_json_response(array("message" => "Invalid ID format."), 400);
    }
}


$conn->close();