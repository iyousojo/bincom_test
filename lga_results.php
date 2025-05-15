<?php
include 'config.php';

// Establish database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch LGAs for dropdown
$lga_sql = "SELECT lga_id, lga_name FROM lga WHERE state_id = 25";
$lga_result = $conn->query($lga_sql);
if (!$lga_result) {
    die("Error fetching LGAs: " . $conn->error);
}

$results = [];
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $lga_id = $_POST['lga_id'];
    $sql = "SELECT party_abbreviation, SUM(party_score) as total_score
            FROM announced_pu_results
            JOIN polling_unit ON announced_pu_results.polling_unit_uniqueid = polling_unit.uniqueid
            JOIN ward ON polling_unit.ward_id = ward.ward_id
            JOIN lga ON ward.lga_id = lga.lga_id
            WHERE lga.lga_id = ? AND lga.state_id = 25
            GROUP BY party_abbreviation";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("i", $lga_id);
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
    <title>Task 2: LGA Results</title>
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
            <h2>Task 2: Summed LGA Results</h2>
            <form method="post" action="">
                <label for="lga_id">Select LGA:</label>
                <select id="lga_id" name="lga_id" required>
                    <option value="">-- Select LGA --</option>
                    <?php while ($lga = $lga_result->fetch_assoc()): ?>
                    <option value="<?php echo $lga['lga_id']; ?>">
                        <?php echo htmlspecialchars($lga['lga_name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                <input type="submit" name="submit" value="Show Results">
            </form>

            <?php if (!empty($results)): ?>
            <table>
                <tr>
                    <th>Party</th>
                    <th>Total Score</th>
                </tr>
                <?php foreach ($results as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['party_abbreviation']); ?></td>
                    <td><?php echo htmlspecialchars($row['total_score']); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php elseif ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
            <div class="message error">No results found for this LGA.</div>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>