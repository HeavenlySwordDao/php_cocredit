<?php
session_start();

// Include database connection
require 'db_connection.php';

// Retrieve the search term
$search = isset($_POST['search']) ? $conn->real_escape_string($_POST['search']) : '';

$sql = "
    SELECT 
        p.payment_id,
        p.borrower_id,
        p.amount AS payment_amount,
        p.deduction AS balance,
        p.payment_date,
        bc.first_name,
        bc.middle_name,
        bc.last_name,
        bc.faculty_department,
        bc.loan_amount
    FROM 
        payments AS p
    JOIN 
        borrower_credentials AS bc ON p.borrower_id = bc.id
    WHERE 1=1
";

if ($search) {
    $sql .= " AND (
        CONCAT(bc.first_name, ' ', bc.middle_name, ' ', bc.last_name) LIKE '%$search%' OR
        bc.faculty_department LIKE '%$search%' OR
        bc.loan_amount LIKE '%$search%' OR
        p.payment_date LIKE '%$search%'
    )";
}

$sql .= " ORDER BY p.payment_date DESC, p.borrower_id DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $fullName = htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['middle_name']) . ' ' . htmlspecialchars($row['last_name']);
        echo "<tr>
                <td>" . htmlspecialchars($row['borrower_id']) . "</td>
                <td>" . $fullName . "</td>
                <td>" . htmlspecialchars($row['faculty_department']) . "</td>
                <td>" . htmlspecialchars($row['loan_amount']) . "</td>
                <td>" . htmlspecialchars($row['payment_amount']) . "</td>
                <td>" . htmlspecialchars($row['balance']) . "</td>
                <td>" . htmlspecialchars($row['payment_date']) . "</td>
                <td>
                    <a href='delete_payment.php?id=" . htmlspecialchars($row['payment_id']) . "' onclick='return confirm(\"Are you sure you want to delete this payment?\");'>Delete</a>
                </td>
            </tr>";
    }
} else {
    echo "<tr><td colspan='8'>No payments found.</td></tr>";
}

$conn->close();
?>
