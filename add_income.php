<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $amount = $_POST['amount'];
    $source = $_POST['source'];
    $description = $_POST['description'];
    $date = $_POST['date'];

    $sql = "INSERT INTO income (user_id, amount, source, description, date) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idsss", $user_id, $amount, $source, $description, $date);
    
    if ($stmt->execute()) {
        header("Location: income.php");
        exit();
    } else {
        $error = "Error adding income: " . $conn->error;
    }
}

// Fetch income sources for dropdown
$sql_sources = "SELECT name FROM income_categories";
$sources = $conn->query($sql_sources);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Income</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center mb-6">
                <a href="income.php" class="text-gray-600 hover:text-gray-800 mr-4">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h2 class="text-2xl font-bold">Add New Income</h2>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-6">
                <!-- Amount Input -->
                <div class="relative">
                    <label class="block text-gray-700 mb-2">Amount</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-gray-500">₱</span>
                        <input type="number" name="amount" step="0.01" required
                               class="w-full pl-8 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                               placeholder="0.00">
                    </div>
                </div>

                <!-- Source Selection -->
                <div>
                    <label class="block text-gray-700 mb-2">Source</label>
                    <div class="relative">
                        <select name="source" required
                                class="w-full px-4 py-2 border rounded-lg appearance-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <?php while($source = $sources->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($source['name']); ?>">
                                    <?php echo htmlspecialchars($source['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <div class="absolute right-3 top-3 text-gray-400 pointer-events-none">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </div>

                <!-- Date Input -->
                <div>
                    <label class="block text-gray-700 mb-2">Date</label>
                    <input type="date" name="date" required
                           value="<?php echo date('Y-m-d'); ?>"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                              placeholder="Add details about this income..."></textarea>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end">
                    <button type="submit" 
                            class="bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        Add Income
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Add client-side validation if needed
        document.querySelector('form').addEventListener('submit', function(e) {
            const amount = document.querySelector('input[name="amount"]').value;
            if (amount <= 0) {
                e.preventDefault();
                alert('Amount must be greater than zero');
            }
        });
    </script>
</body>
</html>