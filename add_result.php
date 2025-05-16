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

</html>