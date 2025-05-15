<?php
include 'config.php';

// Establish database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$results = [];
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $polling_unit_id = $_POST['polling_unit_id'];
    $sql = "SELECT * FROM announced_pu_results WHERE polling_unit_uniqueid = ?"; // Correct column name
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("i", $polling_unit_id);
    if (!$stmt->execute()) {
        die("Error executing statement: " . $stmt->error);
    }
    $result = $stmt->get_result();
    if (!$result) {
        die("Error fetching results: " . $stmt->error);
    }
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task 1: Polling Unit Results</title>
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
            <h2>Task 1: Display Polling Unit Results</h2>
            <form method="post" action="">
                <label for="polling_unit_id">Enter Polling Unit ID:</label>
                <input type="text" id="polling_unit_id" name="polling_unit_id" required>
                <input type="submit" name="submit" value="Show Results">
            </form>

            <?php if (!empty($results)): ?>
            <table>
                <tr>
                    <th>Party</th>
                    <th>Score</th>
                </tr>
                <?php foreach ($results as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['party_abbreviation']); ?></td>
                    <td><?php echo htmlspecialchars($row['party_score']); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php elseif ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
            <div class="message error">No results found for this polling unit.</div>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>