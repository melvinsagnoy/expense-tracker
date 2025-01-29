<?php
session_start();
include('db.php');

// Check if a user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_name = trim($_POST['category_name']);
    $category_description = trim($_POST['category_description']);
    
    // Check if category name is not empty
    if (!empty($category_name)) {
        $stmt = $conn->prepare("INSERT INTO income_categories (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $category_name, $category_description);
        
        if ($stmt->execute()) {
            $_SESSION['toast_message'] = 'Income category added successfully!';
            $_SESSION['toast_message_type'] = 'success';
        } else {
            $_SESSION['toast_message'] = 'Error: ' . $stmt->error;
            $_SESSION['toast_message_type'] = 'error';
        }
    } else {
        $_SESSION['toast_message'] = 'Category name is required!';
        $_SESSION['toast_message_type'] = 'error';
    }
    
    header("Location: income.php");
    exit();
}
?>