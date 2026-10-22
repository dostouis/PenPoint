<?php
// api/get_machines.php
// Returns all machines

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../models/MachineModel.php';

try {
    $machineModel = new MachineModel();
    $machines = $machineModel->getAllMachines();
    
    echo json_encode($machines, JSON_PRETTY_PRINT);
    // DO NOT CLOSE - connection is shared
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to fetch machines: " . $e->getMessage()]);
}
?>