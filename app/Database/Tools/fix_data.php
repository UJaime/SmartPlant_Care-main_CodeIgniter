<?php
require 'config/database.php';
$db = Database::connect();

$res = $db->query("SELECT id FROM plantas WHERE id != 1");
$plantas = $res->fetch_all(MYSQLI_ASSOC);

$res2 = $db->query("SELECT * FROM lecturas_sensores WHERE planta_id = 1");
$lecturas = $res2->fetch_all(MYSQLI_ASSOC);

if (count($lecturas) > 0) {
    foreach ($plantas as $p) {
        $pid = $p['id'];
        $chk = $db->query("SELECT id FROM lecturas_sensores WHERE planta_id = $pid");
        if ($chk->num_rows == 0) {
            foreach ($lecturas as $l) {
                $stmt = $db->prepare("INSERT INTO lecturas_sensores (dispositivo_id, planta_id, humedad_suelo, temperatura, luz_ambiental, nivel_tanque, bateria, riego_activo, creada_en) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iidddiiis", $l['dispositivo_id'], $pid, $l['humedad_suelo'], $l['temperatura'], $l['luz_ambiental'], $l['nivel_tanque'], $l['bateria'], $l['riego_activo'], $l['creada_en']);
                $stmt->execute();
            }
        }
    }
    echo "¡Datos clonados exitosamente para todas las cuentas!";
} else {
    echo "No se encontraron lecturas base en planta 1.";
}
