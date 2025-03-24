# PHP_83_Registration
Anleitung zur Konfiguration des Anmeldesystems

Dieses Anmeldesystem unterstützt zwei verschiedene Speichermethoden und bietet Optionen zur Aktivierung/Deaktivierung der Registrierung und Anmeldung. Die Konfiguration erfolgt über die Datei `config.php` oder direkt im Skript, falls keine `config.php` vorhanden ist.

---

## Konfigurationsmöglichkeiten

Du kannst das Verhalten des Systems mit den folgenden Optionen steuern:

1. Speichermethode:  
   - `file`: Benutzerdaten werden in einer verschlüsselten Datei gespeichert.  
   - `database`: Benutzerdaten werden in einer MySQL-Datenbank gespeichert.  

2. Registrierung und Anmeldung:  
   - `REGISTER_ENABLED`: Aktiviert/Deaktiviert die Registrierung neuer Benutzer.  
   - `LOGIN_ENABLED`: Aktiviert/Deaktiviert die Anmeldung für bestehende Benutzer.  

3. Pfad zur Benutzerdaten-Datei (nur bei file-Methode):  
   - `FILE_PATH`: Definiert den Pfad zur Datei, in der die Benutzerdaten gespeichert werden.  

4. Verschlüsselungsschlüssel (nur bei file-Methode):  
   - `ENCRYPTION_KEY`: Schlüssel zur Verschlüsselung der Benutzerdaten.  

---

## Konfiguration mit `config.php`

1. Erstelle eine neue Datei namens `config.php` im gleichen Verzeichnis wie `register.php`.  
2. Füge den folgenden Code in die `config.php` ein und passe die Werte an:  

```php
<?php
// Datenbankkonfiguration (nur bei 'database'-Speichermethode)
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'dein_benutzername');
define('DB_PASSWORD', 'dein_passwort');
define('DB_NAME', 'deine_datenbank');

// Speichermethode: 'file' oder 'database'
define('STORAGE_METHOD', 'file');

// Registrierung und Anmeldung aktivieren/deaktivieren
define('REGISTER_ENABLED', true);
define('LOGIN_ENABLED', true);

// Nur für 'file'-Speichermethode:
define('FILE_PATH', __DIR__ . '/user.dat');
define('ENCRYPTION_KEY', 'dein_geheimer_schluessel');
?>
```

---

## Manuelle Konfiguration (ohne `config.php`)

Falls keine `config.php` vorhanden ist, kannst du die Einstellungen direkt in der `register.php` anpassen:  

1. Suche den Bereich, in dem geprüft wird, ob die `config.php` existiert:  

```php
if (file_exists(__DIR__ . '/config.php')) {
    require_once 'config.php';
} else {
```

2. Passe in dem `else`-Block die Standardwerte an:  

```php
// Manuelle Konfiguration:
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'dein_benutzername');
define('DB_PASSWORD', 'dein_passwort');
define('DB_NAME', 'deine_datenbank');

// Speichermethode: 'file' oder 'database'
define('STORAGE_METHOD', 'file');

// Registrierung und Anmeldung aktivieren/deaktivieren
define('REGISTER_ENABLED', true);
define('LOGIN_ENABLED', true);

// Nur für 'file'-Speichermethode:
define('USER_FILE', __DIR__ . '/user.dat');
define('ENCRYPTION_KEY', 'dein_geheimer_schluessel');
```

---

## Speicheroptionen

### Option 1: Speicherung in Datei (`file`)

1. Setze in der `config.php` oder im Skript:

```php
define('STORAGE_METHOD', 'file');
```

2. Stelle sicher, dass die Datei `user.dat` im gleichen Verzeichnis wie `register.php` existiert. Falls nicht, wird sie automatisch erstellt.  

### Option 2: Speicherung in Datenbank (`database`)

1. Setze in der `config.php` oder im Skript:

```php
define('STORAGE_METHOD', 'database');
```

2. Erstelle eine MySQL-Datenbank und führe das folgende SQL-Statement aus, um die Tabelle zu erstellen:  

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    firstName VARCHAR(255) NOT NULL,
    lastName VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL
);
```

3. Passe die Datenbankverbindungsdaten in der `config.php` an.  

---

## Testen

1. Rufe `register.php` im Browser auf.  
2. Wenn alles richtig konfiguriert ist, kannst du dich registrieren und anmelden.  
3. Falls eine Fehlermeldung erscheint, überprüfe den Pfad zur `config.php` und die gesetzten Konstanten.  

