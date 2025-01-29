<?php
session_start();
include('db.php');

// Retrieve toast message and type from session if available
$toast_message = isset($_SESSION['toast_message']) ? $_SESSION['toast_message'] : '';
$toast_message_type = isset($_SESSION['toast_message_type']) ? $_SESSION['toast_message_type'] : '';

// Unset session variables after fetching the message
unset($_SESSION['toast_message']);
unset($_SESSION['toast_message_type']);

// Check if a user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $category_name = trim($_POST['category_name']);
    $category_description = trim($_POST['category_description']);
    
    // Check if category name is not empty
    if (!empty($category_name)) {
        $stmt = $conn->prepare("INSERT INTO expense_categories (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $category_name, $category_description);
        
        if ($stmt->execute()) {
            // Set success message to be displayed
            $_SESSION['toast_message'] = 'Category added successfully!';
            $_SESSION['toast_message_type'] = 'success';
            header("Location: expenses.php");
        } else {
            // Set error message to be displayed
            $_SESSION['toast_message'] = 'Error: ' . $stmt->error;
            $_SESSION['toast_message_type'] = 'error';
            header("Location: add_category.php");
        }
    } else {
        // Set error message if category name is empty
        $_SESSION['toast_message'] = 'Category name is required!';
        $_SESSION['toast_message_type'] = 'error';
        header("Location: add_category.php");
    }
    exit(); // Ensure no further code is executed after redirect
}

// Check for any session messages to display
if (isset($_SESSION['toast_message'])) {
    $toast_message = $_SESSION['toast_message'];
    $toast_message_type = $_SESSION['toast_message_type'];
    unset($_SESSION['toast_message']);
    unset($_SESSION['toast_message_type']);
}
?>