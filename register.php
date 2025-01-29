<?php
include('db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $sql = "INSERT INTO users (email, password) VALUES ('$email', '$password')";
    
    if ($conn->query($sql) === TRUE) {
        $success_message = "Registration successful! You can now login.";
    } else {
        $error_message = "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: var(--color-red-800); 
            color: black;
            text-align: center;
            padding: 0.5rem;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex justify-center items-center min-h-screen p-4">
        <div class="bg-white p-8 rounded-lg shadow-lg w-96">
            <div class="flex flex-col items-center mb-6">
                <img src="assets/logo.png" alt="Logo" class="h-32 mb-4">
                <h2 class="text-3xl font-bold text-gray-800">Create Account</h2>
                <p class="text-gray-600 mt-2">Sign up for a new account</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" id="email" 
                           class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 transition duration-150"
                           required>
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" id="password" 
                           class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 transition duration-150"
                           required>
                </div>
                <button type="submit" 
                        class="w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150">
                    Create Account
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Already have an account? 
                    <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">Login here</a>
                </p>
            </div>
        </div>
        
    </div>
    <div class="footer">
        <p>&copy; <?php echo date("Y"); ?> KwartaTally. All rights reserved.</p>
    </div>
</body>
</html>