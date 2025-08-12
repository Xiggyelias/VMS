<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/middleware/security.php';
SecurityMiddleware::initialize();

// Database connection
function getDBConnection() {
    $conn = new mysqli("localhost", "root", "", "vehicleregistrationsystem");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Get database connection
$conn = getDBConnection();

// Start transaction
$conn->begin_transaction();

try {
    // Debug log
    error_log("Received POST data: " . print_r($_POST, true));

    // Insert into applicants table
    $stmt = $conn->prepare("INSERT INTO applicants (studentRegNo, staffsRegNo, fullName, password, phone, Email, college, idNumber, licenseNumber, licenseClass, licenseDate, registrantType) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Hash the password
    $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Handle guest information
    $studentRegNo = $_POST['registrantType'] === 'student' ? $_POST['studentRegNo'] : '';
    $staffRegNo = $_POST['registrantType'] === 'staff' ? $_POST['staffsRegNo'] : '';
    $college = ($_POST['registrantType'] === 'student' || $_POST['registrantType'] === 'staff') ? $_POST['college'] : 'Guest';

    $stmt->bind_param("ssssssssssss", 
        $studentRegNo,
        $staffRegNo,
        $_POST['fullName'],
        $hashed_password,
        $_POST['phone'],
        $_POST['Email'],
        $college,
        $_POST['idNumber'],
        $_POST['licenseNumber'],
        $_POST['licenseClass'],
        $_POST['licenseDate'],
        $_POST['registrantType']
    );

    if (!$stmt->execute()) {
        throw new Exception("Error inserting applicant: " . $stmt->error);
    }

    $applicant_id = $conn->insert_id;

    // Insert vehicles
    if (isset($_POST['vehicles'])) {
        // Accept array directly or decode JSON string
        $vehicles = is_string($_POST['vehicles']) ? json_decode($_POST['vehicles'], true) : $_POST['vehicles'];

        // Check JSON decode error if decoding was attempted
        if (is_string($_POST['vehicles']) && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error decoding vehicles data: " . json_last_error_msg());
        }

        // Start transaction for vehicle insertion
        $conn->begin_transaction();

        try {
            // First, deactivate any existing active vehicles for this applicant
            $deactivate_stmt = $conn->prepare("UPDATE vehicles SET status = 'inactive' WHERE applicant_id = ? AND status = 'active'");
            $deactivate_stmt->bind_param("i", $applicant_id);
            $deactivate_stmt->execute();
            $deactivate_stmt->close();

            // Insert new vehicles with active status
            $vehicle_stmt = $conn->prepare("INSERT INTO vehicles (applicant_id, regNumber, make, owner, address, PlateNumber, status, last_updated) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())");

        foreach ($vehicles as $vehicle) {
            if (!$vehicle_stmt->bind_param("isssss", 
                $applicant_id,
                $vehicle['regNumber'],
                $vehicle['make'],
                $vehicle['owner'],
                $vehicle['address'],
                $vehicle['PlateNumber']
            )) {
                throw new Exception("Error binding vehicle parameters: " . $vehicle_stmt->error);
            }

            if (!$vehicle_stmt->execute()) {
                throw new Exception("Error inserting vehicle: " . $vehicle_stmt->error);
            }

            $vehicle_id = $conn->insert_id;

                // Insert authorized drivers if any
            if (isset($vehicle['drivers']) && is_array($vehicle['drivers'])) {
                $driver_stmt = $conn->prepare("INSERT INTO authorized_driver (vehicle_id, fullname, licenseNumber, contact) VALUES (?, ?, ?, ?)");

                foreach ($vehicle['drivers'] as $driver) {
                    if (!empty($driver['fullName']) && !empty($driver['licenseNumber'])) {
                        $contact = isset($driver['contact']) ? $driver['contact'] : '';

                        if (!$driver_stmt->bind_param("isss", 
                            $vehicle_id,
                            $driver['fullName'],
                            $driver['licenseNumber'],
                            $contact
                        )) {
                            throw new Exception("Error binding driver parameters: " . $driver_stmt->error);
                        }

                        if (!$driver_stmt->execute()) {
                            throw new Exception("Error inserting driver: " . $driver_stmt->error);
                        }
                    }
                }
            }
        }

            // Commit the transaction
            $conn->commit();
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            throw $e;
        }
    }

    // Commit the main transaction
    $conn->commit();

    // Set success message in session
    $_SESSION['registration_success'] = true;

    // Redirect to login page
    header('Location: login.php?registration=success');
    exit();

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();

    // Log the error
    error_log("Registration error: " . $e->getMessage());

    // Display error
    echo "error: " . $e->getMessage();
    exit();
}

// Close the connection
$conn->close();
?>
