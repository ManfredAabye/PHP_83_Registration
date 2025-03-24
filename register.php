<?php
session_start();

// ###########################################################
// ### EINSTELLUNGEN ###
// ###########################################################

// Prüfen, ob config.php existiert und laden, ansonsten Standardwerte setzen
if (file_exists(__DIR__ . '/config.php')) {
    require_once 'config.php';
    //echo "Config.php gefunden und geladen.";
} else {
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

    //echo "Config.php nicht gefunden. Standardwerte verwendet.";
}

// Direkt die definierten Konstanten verwenden
$storageMethod = STORAGE_METHOD;
$register = REGISTER_ENABLED;
$login = LOGIN_ENABLED;
$file = USER_FILE;
$encryptionKey = ENCRYPTION_KEY;

// ###########################################################
// ### EINSTELLUNGEN ENDE ###
// ###########################################################

// Funktionen zum Laden und Speichern der Benutzerdaten
if ($storageMethod === 'file') {
    //$file = __DIR__ . '/user.dat'; // Vollständiger Pfad zur Datei
    //$encryptionKey = 'dein_geheimer_schluessel'; // Verschlüsselungsschlüssel

    function loadUsers(): array {
        global $file, $encryptionKey;
        if (!file_exists($file)) {
            file_put_contents($file, encryptData(json_encode([]), $encryptionKey)); // Erstelle die Datei, falls sie nicht existiert
        }
        $encryptedData = file_get_contents($file);
        return json_decode(decryptData($encryptedData, $encryptionKey), true);
    }

    function saveUsers(array $users): void {
        global $file, $encryptionKey;
        $encryptedData = encryptData(json_encode($users, JSON_PRETTY_PRINT), $encryptionKey);
        file_put_contents($file, $encryptedData);
    }

    // Verschlüsselungsfunktion
    function encryptData($data, $key) {
        $iv = random_bytes(16); // Initialisierungsvektor
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted); // IV und verschlüsselte Daten kombinieren
    }

    // Entschlüsselungsfunktion
    function decryptData($data, $key) {
        $data = base64_decode($data);
        $iv = substr($data, 0, 16); // IV extrahieren
        $encrypted = substr($data, 16); // Verschlüsselte Daten extrahieren
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
} elseif ($storageMethod === 'database') {
    // Verbindung zur Datenbank herstellen
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    // Verbindung überprüfen
    if ($conn->connect_error) {
        die("Verbindung zur Datenbank fehlgeschlagen: " . $conn->connect_error);
    }

    // Tabelle erstellen, falls sie nicht existiert
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        firstName VARCHAR(255) NOT NULL,
        lastName VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL
    )";
    if (!$conn->query($sql)) {
        die("Fehler beim Erstellen der Tabelle: " . $conn->error);
    }

    function loadUsers(): array {
        global $conn;
        $users = [];
        $sql = "SELECT * FROM users";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $users[$row['email']] = [
                    'firstName' => $row['firstName'],
                    'lastName' => $row['lastName'],
                    'password' => $row['password']
                ];
            }
        }
        return $users;
    }

    function saveUsers(array $users): void {
        global $conn;
        // Tabelle leeren
        $conn->query("TRUNCATE TABLE users");

        // Benutzer einfügen
        $stmt = $conn->prepare("INSERT INTO users (email, firstName, lastName, password) VALUES (?, ?, ?, ?)");
        foreach ($users as $email => $user) {
            $stmt->bind_param("ssss", $email, $user['firstName'], $user['lastName'], $user['password']);
            $stmt->execute();
        }
        $stmt->close();
    }
} else {
    die("Ungültige Speichermethode!");
}

$users = loadUsers();

// Captcha generieren
function generateCaptcha($type) {
    if (!isset($_SESSION['captcha']) || !is_array($_SESSION['captcha'])) {
        $_SESSION['captcha'] = [];
    }
    $captchaText = substr(md5(rand()), 0, 6); // 6-stelliger zufälliger Text
    $_SESSION['captcha'][$type] = $captchaText; // In der Session speichern
    return $captchaText;
}

// Captcha-Validierung
function validateCaptcha($userCaptcha, $type) {
    return isset($_SESSION['captcha'][$type]) && $_SESSION['captcha'][$type] === $userCaptcha;
}

// E-Mail-Validierung
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Passwort-Validierung
function validatePassword($password) {
    // Mindestens ein Großbuchstabe, ein Kleinbuchstabe, eine Zahl und ein Sonderzeichen
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password);
}

// Registrierung
if (isset($_POST['register'])) {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirmPassword']);
    $captcha = trim($_POST['captcha']);

    if (!validateCaptcha($captcha, 'register')) {
        $message = "Captcha ist ungültig!";
    } elseif (!validateEmail($email)) {
        $message = "E-Mail ist ungültig!";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwörter stimmen nicht überein!";
    } elseif (!validatePassword($password)) {
        $message = "Das Passwort muss Groß- und Kleinbuchstaben, eine Zahl und ein Sonderzeichen enthalten!";
    } elseif (isset($users[$email])) {
        $message = "E-Mail existiert bereits!";
    } else {
        $users[$email] = [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ];
        saveUsers($users);
        $message = "Registrierung erfolgreich!";
    }
}

// Anmeldung
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $captcha = trim($_POST['captcha']);

    if (!validateCaptcha($captcha, 'login')) {
        $message = "Captcha ist ungültig!";
    } elseif (isset($users[$email]) && password_verify($password, $users[$email]['password'])) {
        $_SESSION['user'] = $users[$email];
        $message = "Anmeldung erfolgreich! Willkommen, {$users[$email]['firstName']} {$users[$email]['lastName']}.";
    } else {
        $message = "Anmeldung fehlgeschlagen!";
    }
}

// Abmeldung
if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: register.php");
    exit;
}

// Captcha generieren, falls noch nicht vorhanden
if (!isset($_SESSION['captcha']['register'])) {
    generateCaptcha('register');
}
if (!isset($_SESSION['captcha']['login'])) {
    generateCaptcha('login');
}

// Captcha-Bild generieren, falls angefragt
if (isset($_GET['captcha_image'])) {
    $type = $_GET['type']; // 'register' oder 'login'
    header('Content-Type: image/png');
    $width = 180; // Breite des Bildes erhöht, um 6 Zeichen anzuzeigen
    $height = 40;
    $image = imagecreate($width, $height);
    $backgroundColor = imagecolorallocate($image, 255, 255, 255);
    $textColor = imagecolorallocate($image, 0, 0, 0);

    // Pfad zur Schriftart-Datei
    $fontPath = __DIR__ . '/arial.ttf'; // Stelle sicher, dass die Datei existiert!

    // Text auf das Bild zeichnen
    imagettftext($image, 20, 0, 10, 30, $textColor, $fontPath, $_SESSION['captcha'][$type]);
    imagepng($image);
    imagedestroy($image);
    exit;
}

// Captcha bei jedem Seitenaufruf neu generieren
generateCaptcha('register');
generateCaptcha('login');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anmeldesystem</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; margin: 0; padding: 0; display: flex; justify-content: center; align-items: center; height: 100vh; flex-direction: column; gap: 2rem; }
        .container { background-color: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px; text-align: center; }
        h1 { color: #333; margin-bottom: 1.5rem; }
        input[type="text"], input[type="password"], input[type="email"] { width: 100%; padding: 0.75rem; margin: 0.5rem 0; border: 1px solid #ccc; border-radius: 4px; font-size: 1rem; }
        button { width: 100%; padding: 0.75rem; margin: 0.5rem 0; border: none; border-radius: 4px; font-size: 1rem; cursor: pointer; }
        button[type="submit"][name="register"] { background-color: #28a745; color: white; }
        button[type="submit"][name="login"] { background-color: #007bff; color: white; }
        button[type="submit"]:hover { opacity: 0.9; }
        a { color: #dc3545; text-decoration: none; display: inline-block; margin-top: 1rem; }
        a:hover { text-decoration: underline; }
        .message { margin-top: 1rem; padding: 0.75rem; border-radius: 4px; background-color: #e9ecef; color: #333; }
        .captcha-image { margin: 0.5rem 0; }
        .password-rules { font-size: 0.9rem; color: #666; margin: 0.5rem 0; }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['user'])): ?>
        <div class="container">
            <h1>Willkommen zurück!</h1>
            <p>Angemeldet als: <b><?= htmlspecialchars($_SESSION['user']['firstName']) ?> <?= htmlspecialchars($_SESSION['user']['lastName']) ?></b></p>
            <a href="?logout=true">Abmelden</a>
        </div>
    <?php else: ?>
        <!-- Registrierungs-Container -->
        <?php if ($register): ?>
            <div class="container">
                <h2>Registrierung</h2>
                <form method="post">
                    <input type="text" name="firstName" placeholder="Vorname" required>
                    <input type="text" name="lastName" placeholder="Nachname" required>
                    <input type="email" name="email" placeholder="E-Mail" required>
                    <input type="password" name="password" placeholder="Passwort" required>
                    <input type="password" name="confirmPassword" placeholder="Passwort wiederholen" required>
                    <div class="password-rules">
                        Das Passwort muss:<br>
                        - Groß- und Kleinbuchstaben enthalten<br>
                        - Mindestens eine Zahl enthalten<br>
                        - Mindestens ein Sonderzeichen enthalten
                    </div>
                    <div class="captcha-image">
                        <img src="?captcha_image=true&type=register" alt="Captcha">
                    </div>
                    <input type="text" name="captcha" placeholder="Captcha eingeben" required>
                    <button type="submit" name="register">Registrieren</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Anmelde-Container -->
        <?php if ($login): ?>
            <div class="container">
                <h2>Anmeldung</h2>
                <form method="post">
                    <input type="email" name="email" placeholder="E-Mail" required>
                    <input type="password" name="password" placeholder="Passwort" required>
                    <div class="captcha-image">
                        <img src="?captcha_image=true&type=login" alt="Captcha">
                    </div>
                    <input type="text" name="captcha" placeholder="Captcha eingeben" required>
                    <button type="submit" name="login">Anmelden</button>
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Nachrichten-Container -->
    <?php if (isset($message)): ?>
        <div class="container message">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
</body>
</html>