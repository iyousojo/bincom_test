<?php
require_once 'config.php'; // Must include session_start()
require_once 'db.php';

// Initialize database connection
$db = new Database();
$conn = $db->conn;
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
error_log("CSRF Token Generated: $csrf_token, Session ID: " . session_id());

// Fetch valid parties (your existing code)
$valid_parties = [];
$party_query = "SELECT partyid FROM party";
$party_result = $conn->query($party_query);
if ($party_result) {
    while ($row = $party_result->fetch_assoc()) {
        $valid_parties[] = $row['partyid'];
    }
    $party_result->free();
} else {
    error_log("Failed to fetch parties: " . $conn->error);
    $valid_parties = ['PDP', 'DPP', 'ACN', 'PPA', 'CDC', 'JP', 'ANPP', 'LABO', 'CPP'];
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $post_csrf = $_POST['csrf_token'] ?? 'not_set';
    $session_csrf = $_SESSION['csrf_token'] ?? 'not_set';
    error_log("CSRF Check: POST=$post_csrf, SESSION=$session_csrf, Session ID=" . session_id());

    if ($post_csrf !== $session_csrf || empty($post_csrf)) {
        $message = "Invalid CSRF token. Please try again.";
        error_log("CSRF validation failed: POST=$post_csrf, SESSION=$session_csrf");
    } else {
        // Your existing form processing logic
        $polling_unit_id = trim($_POST['polling_unit_id']);
        $party_abbreviation = strtoupper(trim($_POST['party_abbreviation']));
        $party_score = (int)$_POST['party_score'];
        $entered_by_user = trim($_POST['entered_by_user']);
        $user_ip_address = $_SERVER['REMOTE_ADDR'];
        $date_entered = date('Y-m-d H:i:s');

        // Your validation and database logic (unchanged)
        // ... [rest of your code] ...
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task 3: Add New Results</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <header>
        <h1>Bincom Recruitment Test</h1>
    </header>
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="polling_unit.php">Task 1: Polling Unit Results</a></li>
            <li><a href="lga_results.php">Task 2: LGA Results</a></li>
            <li><a href="add_result.php">Task 3: Add New Results</a></li>
        </ul>
    </nav>
    <main>
        <div class="container">
            <h2>Task 3: Add New Results</h2>
            <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <label for="polling_unit_id">Polling Unit ID:</label>
                <input type="text" id="polling_unit_id" name="polling_unit_id" maxlength="50" required>

                <label for="party_abbreviation">Party Abbreviation:</label>
                <select id="party_abbreviation" name="party_abbreviation" required>
                    <?php foreach ($valid_parties as $party): ?>
                    <option value="<?php echo htmlspecialchars($party); ?>"><?php echo htmlspecialchars($party); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <label for="party_score">Party Score:</label>
                <input type="number" id="party_score" name="party_score" min="0" required>

                <label for="entered_by_user">Entered By User:</label>
                <input type="text" id="entered_by_user" name="entered_by_user" maxlength="50" required>

                <input type="submit" name="submit" value="Add Result">
            </form>
        </div>
    </main>
</body>

</html><?php
// add_result.php - Add new polling unit result
require_once 'config.php'; // Assumes config.php handles session_start()
require_once 'db.php';

// Initialize database connection
$db = new Database();
$conn = $db->conn;

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Generate CSRF token only if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Fetch valid parties from party table
$valid_parties = [];
$party_query = "SELECT partyid FROM party";
$party_result = $conn->query($party_query);
if ($party_result) {
    while ($row = $party_result->fetch_assoc()) {
        $valid_parties[] = $row['partyid'];
    }
    $party_result->free();
} else {
    error_log("Failed to fetch parties: " . $conn->error);
    $valid_parties = ['PDP', 'DPP', 'ACN', 'PPA', 'CDC', 'JP', 'ANPP', 'LABO', 'CPP']; // Fallback
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    // Debug CSRF token
    $post_csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : 'not set';
    $session_csrf = $_SESSION['csrf_token'] ?? 'not set';
    error_log("CSRF Debug: POST CSRF=$post_csrf, SESSION CSRF=$session_csrf, Session ID=" . session_id());

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Invalid CSRF token. Please try again.";
        error_log("CSRF validation failed: POST=" . print_r($_POST, true) . ", SESSION=" . print_r($_SESSION, true));
    } else {
        $polling_unit_id = trim($_POST['polling_unit_id']);
        $party_abbreviation = strtoupper(trim($_POST['party_abbreviation']));
        $party_score = (int)$_POST['party_score'];
        $entered_by_user = trim($_POST['entered_by_user']);
        $user_ip_address = $_SERVER['REMOTE_ADDR'];
        $date_entered = date('Y-m-d H:i:s');

        // Validate inputs
        $errors = [];
        if (empty($polling_unit_id) || strlen($polling_unit_id) > 50) {
            $errors[] = "Polling Unit ID is required and must be 50 characters or less.";
        } else {
            // Validate polling unit ID exists in polling_unit table
            $check_pu_sql = "SELECT COUNT(*) FROM polling_unit WHERE uniqueid = ?";
            $check_pu_stmt = $conn->prepare($check_pu_sql);
            if ($check_pu_stmt) {
                $pu_id_int = (int)$polling_unit_id;
                $check_pu_stmt->bind_param("i", $pu_id_int);
                $check_pu_stmt->execute();
                $check_pu_stmt->bind_result($pu_count);
                $check_pu_stmt->fetch();
                $check_pu_stmt->close();
                if ($pu_count == 0) {
                    $errors[] = "Invalid Polling Unit ID. It does not exist in the database.";
                }
            } else {
                $errors[] = "Error checking polling unit: " . $conn->error;
                error_log("Polling unit check failed: " . $conn->error);
            }
        }
        if (empty($party_abbreviation) || !in_array($party_abbreviation, $valid_parties)) {
            $errors[] = "Party Abbreviation is invalid. Must be one of: " . implode(', ', $valid_parties);
        }
        if ($party_score < 0 || !is_numeric($_POST['party_score'])) {
            $errors[] = "Party Score must be a non-negative number.";
        }
        if (empty($entered_by_user) || strlen($entered_by_user) > 50) {
            $errors[] = "Entered By User is required and must be 50 characters or less.";
        }

        if (!empty($errors)) {
            $message = "Validation errors: " . implode(" ", $errors);
        } else {
            // Check for duplicate entry
            $check_sql = "SELECT COUNT(*) FROM announced_pu_results WHERE polling_unit_uniqueid = ? AND party_abbreviation = ?";
            $check_stmt = $conn->prepare($check_sql);
            if ($check_stmt) {
                $check_stmt->bind_param("ss", $polling_unit_id, $party_abbreviation);
                $check_stmt->execute();
                $check_stmt->bind_result($count);
                $check_stmt->fetch();
                $check_stmt->close();

                if ($count > 0) {
                    // Update existing result
                    $update_sql = "UPDATE announced_pu_results SET party_score = ?, entered_by_user = ?, date_entered = ?, user_ip_address = ? 
                                   WHERE polling_unit_uniqueid = ? AND party_abbreviation = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    if ($update_stmt) {
                        $update_stmt->bind_param("isssss", $party_score, $entered_by_user, $date_entered, $user_ip_address, $polling_unit_id, $party_abbreviation);
                        if ($update_stmt->execute()) {
                            if ($update_stmt->affected_rows > 0) {
                                $message = "Result updated successfully!";
                            } else {
                                $message = "No changes made to existing result.";
                                error_log("No rows affected for update: polling_unit_id=$polling_unit_id, party=$party_abbreviation");
                            }
                        } else {
                            $message = "Error updating result: " . $update_stmt->error;
                            error_log("Update failed: " . $update_stmt->error);
                        }
                        $update_stmt->close();
                    } else {
                        $message = "Error preparing update statement: " . $conn->error;
                        error_log("Update prepare failed: " . $conn->error);
                    }
                } else {
                    // Insert new result
                    $sql = "INSERT INTO announced_pu_results (polling_unit_uniqueid, party_abbreviation, party_score, entered_by_user, date_entered, user_ip_address) 
                            VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    if (!$stmt) {
                        $message = "Error preparing statement: " . $conn->error;
                        error_log("Prepare failed: " . $conn->error);
                    } else {
                        $stmt->bind_param("ssisss", $polling_unit_id, $party_abbreviation, $party_score, $entered_by_user, $date_entered, $user_ip_address);
                        if ($stmt->execute()) {
                            if ($stmt->affected_rows > 0) {
                                $message = "Result added successfully!";
                            } else {
                                $message = "No rows affected“… please check constraints.";
                                error_log("No rows affected for polling_unit_id: $polling_unit_id, party: $party_abbreviation");
                            }
                        } else {
                            $message = "Error adding result: " . $stmt->error;
                            error_log("Execute failed: " . $stmt->error);
                        }
                        $stmt->close();
                    }
                }
            } else {
                $message = "Error preparing duplicate check: " . $conn->error;
                error_log("Duplicate check prepare failed: " . $conn->error);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task 3: Add New Results</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <header>
        <h1>Bincom Recruitment Test</h1>
    </header>
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="polling_unit.php">Task 1: Polling Unit Results</a></li>
            <li><a href="lga_results.php">Task 2: LGA Results</a></li>
            <li><a href="add_result.php">Task 3: Add New Results</a></li>
        </ul>
    </nav>
    <main>
        <div class="container">
            <h2>Task 3: Add New Results</h2>
            <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <label for="polling_unit_id">Polling Unit ID:</label>
                <input type="text" id="polling_unit_id" name="polling_unit_id" maxlength="50" required>

                <label for="party_abbreviation">Party Abbreviation:</label>
                <select id="party_abbreviation" name="party_abbreviation" required>
                    <?php foreach ($valid_parties as $party): ?>
                    <option value="<?php echo htmlspecialchars($party); ?>"><?php echo htmlspecialchars($party); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <label for="party_score">Party Score:</label>
                <input type="number" id="party_score" name="party_score" min="0" required>

                <label for="entered_by_user">Entered By User:</label>
                <input type="text" id="entered_by_user" name="entered_by_user" maxlength="50" required>

                <input type="submit" name="submit" value="Add Result">
            </form>
        </div>
    </main>
</body>

</html>