<?php
session_start();
// Include database connection
require 'db_connection.php';

// Initialize user data
$user = [];

// Check if the user is logged in and is a borrower
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'borrower') {
    header("Location: login.html");
    exit();
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_picture'])) {
    $target_dir = "uploads/"; // Directory to store uploaded files
    // Ensure uploads directory exists
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    $target_file = $target_dir . basename($_FILES["profile_picture"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is an actual image or fake image
    if (!empty($_FILES["profile_picture"]["tmp_name"])) {
        $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
        if ($check === false) {
            echo "File is not an image.";
            $uploadOk = 0;
        }
    } else {
        echo "No file uploaded.";
        $uploadOk = 0;
    }

    // Check file size (limit to 1MB)
    if ($_FILES["profile_picture"]["size"] > 1048576) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Allow certain file formats
    if (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
        echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
    } else {
        // Try to upload file
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            // Save user data to the database (include the profile picture path)
            $username = $_SESSION['username']; // Assuming username is stored in session
            $sql = "UPDATE users SET profile_picture=? WHERE username=?"; // Use prepared statements

            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ss', $target_file, $username); // Bind parameters
            
            if ($stmt->execute()) {
                echo "Profile picture updated successfully.";
            } else {
                echo "Error updating profile picture: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }
}

// Fetch user data
$username = $_SESSION['username'];
$sql = "SELECT username, email, role, profile_picture FROM users WHERE username=? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $username); // Bind parameter
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "No user found.";
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="borrowers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>

/* Profile Container */
.profile-container {
    display: flex;
    justify-content: center;
    margin-top: 20px;
    margin-left: 280px; /* Increased margin for more space from the sidebar */
    flex-wrap: wrap; /* Allow wrapping for small screens */
    width: 100%;
    box-sizing: border-box; /* Ensure padding and margin are considered in width calculation */
}

/* Profile Card */
.profile-card {
    border: 2px solid #ddd;
    border-radius: 15px;
    padding: 20px;
    background-color: #ffffff;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    width: 400px;
    text-align: center;
    height: auto;
    max-height: 500px;
    overflow: hidden;
    margin: 10px; /* Add spacing between profile cards on smaller screens */
}

.profile-card img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    margin-bottom: 15px;
    border: 4px solid #007bff;
    object-fit: cover;
    transition: transform 0.3s;
}

.profile-card img:hover {
    transform: scale(1.05);
}

/* Profile Info Styling */
.profile-info {
    margin: 10px 0;
    font-size: 18px;
    color: #333;
    text-align: left;
    padding: 0 10px;
}

/* File Upload Section */
.form-group {
    margin-top: 15px;
    margin-bottom: 20px;
    text-align: left;
}

input[type="file"] {
    display: none;
}

.custom-file-label {
    background-color: #007bff;
    color: white;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    display: inline-block;
    transition: background-color 0.3s;
}

.custom-file-label:hover {
    background-color: #0056b3;
}

/* Button Styling */
.btn {
    background-color: #28a745;
    color: #fff;
    border: none;
    padding: 12px 20px;
    cursor: pointer;
    border-radius: 5px;
    transition: background-color 0.3s ease, transform 0.3s;
    width: 100%;
}

.btn:hover {
    background-color: #218838;
    transform: translateY(-2px);
}

.btn:active {
    transform: translateY(0);
}

/* Responsive Styles */
@media (max-width: 1024px) {
    .profile-container {
        margin-left: 150px; /* Remove left margin for smaller screens */
        justify-content: center; /* Center profile card on smaller screens */
    }

    .profile-card {
        width: 90%; /* Make profile card width smaller on medium screens */
        max-width: 450px; /* Set a max width */
    }

    .profile-info {
        font-size: 16px; /* Adjust font size for better readability */
    }
}

@media (max-width: 768px) {
    .profile-container {
        flex-direction: column; /* Stack profile card vertically */
        align-items: center; /* Center the profile card */
        margin-left: 90px; /* Adjust for smaller screens */
    }

    .profile-card {
        width: 80%; /* Make the profile card take more space */
        margin: 20px 0; /* Add margin between stacked elements */
    }

    .profile-info {
        font-size: 14px; /* Adjust font size for smaller screens */
    }

    .btn {
        padding: 10px; /* Adjust button size for smaller screens */
    }
}

@media (max-width: 480px) {
    .profile-card img {
        width: 100px; /* Resize profile image for very small screens */
        height: 100px; /* Ensure square image */
    }

    .profile-card {
        width: 90%; /* Take up more space on small screens */
        padding: 15px; /* Adjust padding */
    }

    .profile-info {
        font-size: 14px; /* Adjust font size for readability */
    }

    .btn {
        padding: 8px; /* Adjust button size for very small screens */
    }
}


    </style>
</head>
<body>
<header>
    <div class="form-image">
        <img src="logo.png" alt="Logo Image">
    </div>
    <nav>
        <ul>
            <li class="user-info">
                <span class="user-name"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest'; ?></span>
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

<main>
    <div class="sidebar">
        <ul>
            <li><a href="borrower_dashboard.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="Loan_types.php"><i class="fas fa-money-bill-wave"></i> Loan Types</a></li>
            <li><a href="Loan_plans.php"><i class="fas fa-calendar-alt"></i> Loan Plans</a></li>
            <li><a href="user_profile.php"><i class="fas fa-user"></i> Profile</a></li>
        </ul>
    </div>

    <div class="profile-container">
        <div class="profile-card">
            <!-- Profile Picture -->
            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture">
            
            <!-- Username -->
            <div class="profile-info">
                <p><strong>Username:</strong> <?php echo ucfirst(htmlspecialchars($user['username'])); ?></p>
            </div>
            
            <!-- Email -->
            <div class="profile-info">
                <p><strong>Email:</strong> <?php echo (htmlspecialchars($user['email'])); ?></p>
            </div>

            <!-- Role -->
            <div class="profile-info">
                <p><strong>Role:</strong> <?php echo ucfirst(htmlspecialchars($user['role'])); ?></p>
            </div>

            <!-- Profile Picture Upload Form -->
            <form action="user_profile.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="profile_picture" class="custom-file-label">Choose File</label>
                    <input type="file" name="profile_picture" id="profile_picture" accept="image/*">
                </div>
                <button type="submit" class="btn" id="uploadBtn">Upload Profile Picture</button>
            </form>
        </div>
    </div>
</main>
<footer>
    <p>&copy; 2024 Your Company. All Rights Reserved.</p>
</footer>
</body>
</html>
