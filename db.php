<?php
// db.php - Database operations class
require_once 'config.php';

class Database {
    private $host = "localhost";
    private $user = "root";
    private $password = "";
    private $dbname = "bincom_test"; // Correct database name
    public $conn;

    public function __construct() {
        $this->conn = new mysqli($this->host, $this->user, $this->password, $this->dbname);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    // Validate polling unit ID
    public function isValidPollingUnit($polling_unit_id) {
        $stmt = $this->conn->prepare("SELECT 1 FROM announced_pu_results WHERE polling_unit_uniqueid = ? 
                                      UNION SELECT 1 FROM agentname WHERE pollingunit_uniqueid = ?");
        $stmt->bind_param("ss", $polling_unit_id, $polling_unit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    // Get polling unit results by ID
    public function getPollingUnitResults($polling_unit_id) {
        try {
            if (!$this->isValidPollingUnit($polling_unit_id)) {
                return ['error' => 'Invalid polling unit ID'];
            }
            $stmt = $this->conn->prepare("SELECT party_abbreviation, party_score, entered_by_user, date_entered 
                                          FROM announced_pu_results 
                                          WHERE polling_unit_uniqueid = ?");
            $stmt->bind_param("s", $polling_unit_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $results = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $results;
        } catch (Exception $e) {
            return ['error' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Get LGA results by LGA ID
    public function getLGAResults($lga_id) {
        try {
            $stmt = $this->conn->prepare("SELECT alr.party_abbreviation, alr.party_score, alr.entered_by_user, alr.date_entered, l.lga_name 
                                          FROM announced_lga_results alr 
                                          JOIN lga l ON alr.lga_name = l.lga_id 
                                          WHERE alr.lga_name = ?");
            $stmt->bind_param("s", $lga_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $results = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $results;
        } catch (Exception $e) {
            return ['error' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Get all agents
    public function getAgents() {
        try {
            $query = "SELECT name_id, firstname, lastname, email, phone, pollingunit_uniqueid 
                      FROM agentname";
            $result = $this->conn->query($query);
            $agents = $result->fetch_all(MYSQLI_ASSOC);
            return $agents;
        } catch (Exception $e) {
            return ['error' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Add new polling unit result
    public function addPollingUnitResult($polling_unit_id, $party_abbreviation, $party_score, $entered_by_user, $user_ip) {
        try {
            if (!$this->isValidPollingUnit($polling_unit_id)) {
                return false;
            }
            $entered_by_user = empty($entered_by_user) ? null : $entered_by_user;
            $stmt = $this->conn->prepare("INSERT INTO announced_pu_results 
                                          (polling_unit_uniqueid, party_abbreviation, party_score, entered_by_user, date_entered, user_ip_address) 
                                          VALUES (?, ?, ?, ?, NOW(), ?)");
            $stmt->bind_param("ssiss", $polling_unit_id, $party_abbreviation, $party_score, $entered_by_user, $user_ip);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Exception $e) {
            return false;
        }
    }

    // Get all LGAs
    public function getLGAs() {
        try {
            $query = "SELECT lga_id, lga_name FROM lga";
            $result = $this->conn->query($query);
            $lgas = $result->fetch_all(MYSQLI_ASSOC);
            return $lgas;
        } catch (Exception $e) {
            return ['error' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function __destruct() {
        $this->conn->close();
    }
}
?>