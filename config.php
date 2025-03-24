<?php
// Standardwerte manuell festlegen
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'your_username');
define('DB_PASSWORD', 'your_password');
define('DB_NAME', 'your_database');

// Speichermethode auswählen: 'file' oder 'database'
define('STORAGE_METHOD', 'file');

// Registrierung und Anmeldung aktivieren/deaktivieren
define('REGISTER_ENABLED', true); // true oder false
define('LOGIN_ENABLED', true);    // true oder false

// Pfad zur Datei und Verschlüsselungsschlüssel
define('USER_FILE', __DIR__ . '/user.dat');
define('ENCRYPTION_KEY', 'dein_geheimer_schluessel');
?>
