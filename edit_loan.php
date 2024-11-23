<?php 
session_start();
// Include database connection
require 'db_connection.php';

// Check if the loan ID is provided
if (isset($_GET['id'])) {
    $loan_id = $_GET['id'];

    // Fetch the loan details from the database based on loan ID
    $sql = 'SELECT first_name, middle_name, last_name, address, contact_no, net_pay, faculty_department, 
                   loan_type, loan_amount, loan_purpose, loan_term, payslip 
            FROM borrower_credentials WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $loan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $loan = $result->fetch_assoc();

    if (!$loan) {
        die("Loan not found.");
    }

    // Handle form submission for editing loan details
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Initialize arrays for fields to update
        $fields = [];
        $values = [];

        // Check if each field is set and add it to the fields array
        if (!empty($_POST['first_name'])) {
            $fields[] = "first_name = ?";
            $values[] = strtoupper($_POST['first_name']);
        }
        if (!empty($_POST['middle_name'])) {
            $fields[] = "middle_name = ?";
            $values[] = strtoupper($_POST['middle_name']);
        }
        if (!empty($_POST['last_name'])) {
            $fields[] = "last_name = ?";
            $values[] = strtoupper($_POST['last_name']);
        }
        if (!empty($_POST['address'])) {
            $fields[] = "address = ?";
            $values[] = strtoupper($_POST['address']);
        }
        if (!empty($_POST['contact_no'])) {
            $fields[] = "contact_no = ?";
            $values[] = $_POST['contact_no'];
        }
        if (!empty($_POST['net_pay'])) {
            $fields[] = "net_pay = ?";
            $values[] = $_POST['net_pay'];
        }
        if (!empty($_POST['faculty_department'])) {
            $fields[] = "faculty_department = ?";
            $values[] = $_POST['faculty_department'];
        }
        if (!empty($_POST['loan_type'])) {
            $fields[] = "loan_type = ?";
            $values[] = strtoupper($_POST['loan_type']);
        }
        if (!empty($_POST['loan_amount'])) {
            $fields[] = "loan_amount = ?";
            $values[] = $_POST['loan_amount'];
        }
        if (!empty($_POST['loan_purpose'])) {
            $fields[] = "loan_purpose = ?";
            $values[] = strtoupper($_POST['loan_purpose']);
        }
        if (!empty($_POST['loan_term'])) {
            $fields[] = "loan_term = ?";
            $values[] = $_POST['loan_term'];
        }

        // Handle payslip file upload if a new file is uploaded
        if (isset($_FILES['payslip']) && $_FILES['payslip']['error'] == 0) {
            // Handle the file upload
            $payslip = $_FILES['payslip'];
            $upload_dir = 'uploads/'; // Directory to save the uploaded files
            $upload_file = $upload_dir . basename($payslip['name']);
            $upload_ok = 1; // Variable to check if the upload is successful

            // Check file type and size
            $file_type = strtolower(pathinfo($upload_file, PATHINFO_EXTENSION));
            if ($payslip['size'] > 2000000) { // Limit to 2MB
                echo "Sorry, your file is too large.";
                $upload_ok = 0;
            }
            if (!in_array($file_type, ['pdf', 'jpg', 'jpeg', 'doc', 'docx', 'png'])) {
                echo "Sorry, only PDF, JPG, JPEG, DOC, DOCX & PNG files are allowed.";
                $upload_ok = 0;
            }

            // Attempt to upload the file if checks are passed
            if ($upload_ok == 1) {
                if (move_uploaded_file($payslip['tmp_name'], $upload_file)) {
                    // File uploaded successfully, add to fields for update
                    $fields[] = "payslip = ?";
                    $values[] = $payslip['name'];
                } else {
                    echo "Error uploading the file.";
                }
            }
        }

        // If there are fields to update, prepare and execute the update query
        if (count($fields) > 0) {
            $update_sql = 'UPDATE borrower_credentials SET ' . implode(', ', $fields) . ' WHERE id = ?';
            $values[] = $loan_id; // Add loan ID at the end for the WHERE clause
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param(str_repeat("s", count($values) - 1) . 'i', ...$values);

            if ($update_stmt->execute()) {
                // Redirect to loan details page after successful update
                header("Location: loan_details.php");
                exit;
            } else {
                echo "Error updating loan details: " . htmlspecialchars($conn->error);
            }
        }
    }
} else {
    die("No loan ID provided.");
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Loan Details</title>
    <link rel="stylesheet" href="borrowers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<style>
        .content {
            margin-left: 220px;
            padding: 20px;
            flex: 1;
            margin-top: 100px; /* Space for header */
        }

        .registration-form-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 15px;
        }

        .input-group {
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
        }

        input[type="text"], input[type="number"], select, input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px solid #ccc;
            border-radius: 4px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus, input[type="number"]:focus, select:focus {
            border-color: #007BFF;
            outline: none;
        }

        .button-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            color: white;
            background-color: green; /* Green */
            cursor: pointer;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .btn:hover {
            background-color: #218838; /* Darker green */
        }

        .btn-secondary {
            background-color: red; /* Red */
        }

        .btn-secondary:hover {
            background-color: #c82333; /* Darker red */
        }
 /* Responsive styles */
 @media (max-width: 768px) {

            .content {
                margin-left: 160px;
                width: calc(100% - 160px);
            }

            .form-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
</style>
<body>
    <header>
        <div class="form-image">
            <img src="logo.png" alt="Logo Image">
        </div>
        <nav>
            <ul>
                <li class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="#" class="logout-icon">
                        <i class="fas fa-user"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </li>
            </ul>
        </nav>
    </header>

    <div class="sidebar">
        <ul>
            <li><a href="borrower_dashboard.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="Loan_types.php"><i class="fas fa-money-bill-wave"></i> Loan Types</a></li>
            <li><a href="Loan_plans.php"><i class="fas fa-calendar-alt"></i> Loan Plans</a></li>
            <li><a href="user_profile.php"><i class="fas fa-user"></i> Profile</a></li>
        </ul>
    </div>

    <div class="content">
        <div class="registration-form-container">
            <h2>Edit Loan Details</h2>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="input-group">
                        <label for="last-name">Last Name:</label>
                        <input type="text" id="last-name" name="last_name" value="<?php echo htmlspecialchars($loan['last_name']); ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="first-name">First Name:</label>
                        <input type="text" id="first-name" name="first_name" value="<?php echo htmlspecialchars($loan['first_name']); ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="middle-name">Middle Name:</label>
                        <input type="text" id="middle-name" name="middle_name" value="<?php echo htmlspecialchars($loan['middle_name']); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="input-group">
                        <label for="address">Address:</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($loan['address']); ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="contact_no">Contact No:</label>
                        <input type="text" id="contact_no" name="contact_no" value="<?php echo htmlspecialchars($loan['contact_no']); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="input-group">
                        <label for="faculty_department">Faculty/Department:</label>
                        <select id="faculty_department" name="faculty_department" required>
                            <option value="CCJE" <?php if ($loan['faculty_department'] == 'CCJE') echo 'selected'; ?>>CCJE</option>
                            <option value="CCSCIT" <?php if ($loan['faculty_department'] == 'CCSCIT') echo 'selected'; ?>>CCSCIT</option>
                            <option value="CDCAS" <?php if ($loan['faculty_department'] == 'CDCAS') echo 'selected'; ?>>CDCAS</option>
                            <option value="CED" <?php if ($loan['faculty_department'] == 'CED') echo 'selected'; ?>>CED</option>
                            <option value="CFEM" <?php if ($loan['faculty_department'] == 'CFEM') echo 'selected'; ?>>CFEM</option>
                            <option value="IBM" <?php if ($loan['faculty_department'] == 'IBM') echo 'selected'; ?>>IBM</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label for="loan_type">Loan Type:</label>
                        <select id="loan_type" name="loan_type" required>
                            <option value="Regular" <?php if ($loan['loan_type'] == 'Regular') echo 'selected'; ?>>Regular</option>
                            <option value="Special" <?php if ($loan['loan_type'] == 'Special') echo 'selected'; ?>>Special</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label for="loan_term">Loan Term (months):</label>
                        <select id="loan_term" name="loan_term" required>
                            <option value="6" <?php if ($loan['loan_term'] == '6') echo 'selected'; ?>>6</option>
                            <option value="12" <?php if ($loan['loan_term'] == '12') echo 'selected'; ?>>12</option>
                            <option value="24" <?php if ($loan['loan_term'] == '24') echo 'selected'; ?>>24</option>
                            <option value="Custom" <?php if ($loan['loan_term'] == 'Custom') echo 'selected'; ?>>Custom</option>
                        </select>
                    </div>
                </div>
                <div class="button-container">
                    <button type="submit" class="btn">Save Changes</button>
                    <a href="loan_details.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 Your Company. All rights reserved.</p>
    </footer>
</body>
</html>