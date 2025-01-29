<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$current_month = date('Y-m');

// Fetch all expense categories
$sql_categories = "SELECT DISTINCT category FROM expenses WHERE user_id = $user_id";
$categories = $conn->query($sql_categories);

// Fetch existing budgets for current month
$sql_budgets = "SELECT category, amount FROM budgets 
                WHERE user_id = $user_id AND month = '$current_month'";
$existing_budgets = $conn->query($sql_budgets);
$budget_data = [];
while ($budget = $existing_budgets->fetch_assoc()) {
    $budget_data[$budget['category']] = $budget['amount'];
}

// Fetch actual expenses for current month
$sql_expenses = "SELECT category, SUM(amount) as total 
                 FROM expenses 
                 WHERE user_id = $user_id AND DATE_FORMAT(date, '%Y-%m') = '$current_month'
                 GROUP BY category";
$expenses = $conn->query($sql_expenses);
$expense_data = [];
while ($expense = $expenses->fetch_assoc()) {
    $expense_data[$expense['category']] = $expense['total'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    
    // Check if budget already exists for this category and month
    $check_sql = "SELECT id FROM budgets 
                  WHERE user_id = $user_id 
                  AND category = ? 
                  AND month = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ss", $category, $current_month);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing budget
        $update_sql = "UPDATE budgets 
                      SET amount = ? 
                      WHERE user_id = ? 
                      AND category = ? 
                      AND month = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("diss", $amount, $user_id, $category, $current_month);
    } else {
        // Insert new budget
        $insert_sql = "INSERT INTO budgets (user_id, category, amount, month) 
                      VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("isds", $user_id, $category, $amount, $current_month);
    }
    
    if ($stmt->execute()) {
        $_SESSION['toast_message'] = 'Budget updated successfully!';
        $_SESSION['toast_message_type'] = 'success';
    } else {
        $_SESSION['toast_message'] = 'Error updating budget.';
        $_SESSION['toast_message_type'] = 'error';
    }
    header("Location: budgets.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Management</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById("sidebar").classList.toggle("w-16");
            document.getElementById("sidebar").classList.toggle("w-64");
            let links = document.querySelectorAll(".sidebar-text");
            links.forEach(link => link.classList.toggle("hidden"));
        }
    </script>
</head>
<body class="bg-gray-100">
    <div class="flex">
        <!-- Sidebar -->
    <div id="sidebar" class="w-64 h-screen bg-blue-800 text-white fixed transition-all duration-300">
        <div class="p-4 flex justify-between">
            <h2 class="text-2xl font-bold sidebar-text">ExpenseTracker</h2>
            <button onclick="toggleSidebar()" class="focus:outline-none">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <nav>
            <a href="#" class="block py-4 px-4 hover:bg-blue-700  ">
                <i class="fas fa-home mx-auto"></i>
                <span class="ml-4 sidebar-text">Dashboard</span>
            </a>
            <a href="expenses.php" class="block py-4 px-4 hover:bg-blue-700  items-center">
                <i class="fas fa-receipt mx-auto"></i>
                <span class="ml-4 sidebar-text">Expenses</span>
            </a>
            <a href="income.php" class="block py-4 px-4 hover:bg-blue-700  items-center">
                <i class="fas fa-money-bill-wave mx-auto"></i>
                <span class="ml-4 sidebar-text">Income</span>
            </a>
            <a href="budgets.php" class="block py-4 px-4 hover:bg-blue-700  items-center">
                <i class="fas fa-piggy-bank mx-auto"></i>
                <span class="ml-4 sidebar-text">Budgets</span>
            </a>
            <a href="reports.php" class="block py-4 px-4 hover:bg-blue-700  items-center">
                <i class="fas fa-chart-bar mx-auto"></i>
                <span class="ml-4 sidebar-text">Reports</span>
            </a>
            <a href="logout.php" class="block py-4 px-4 hover:bg-red-700  items-center mt-8">
                <i class="fas fa-sign-out-alt mx-auto"></i>
                <span class="ml-4 sidebar-text">Logout</span>
            </a>
        </nav>
    </div>

        <!-- Main Content -->
        <div class="ml-64 flex-1 p-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Budget Management</h1>
                <button onclick="openModal()" 
                        class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                    <i class="fas fa-plus mr-2"></i>Set Budget
                </button>
            </div>

            <!-- Toast Message -->
            <?php if (isset($_SESSION['toast_message'])): ?>
                <div id="toast" class="bg-<?php echo $_SESSION['toast_message_type'] === 'success' ? 'green' : 'red'; ?>-500 text-white px-4 py-2 rounded-lg mb-4">
                    <?php echo $_SESSION['toast_message']; ?>
                </div>
                <?php unset($_SESSION['toast_message'], $_SESSION['toast_message_type']); ?>
            <?php endif; ?>

            <!-- Budget Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Budget Progress -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Budget Overview</h3>
                    <?php foreach($budget_data as $category => $budget): ?>
                        <?php 
                            $spent = $expense_data[$category] ?? 0;
                            $percentage = $budget > 0 ? ($spent / $budget) * 100 : 0;
                            $color_class = $percentage > 90 ? 'bg-red-500' : ($percentage > 70 ? 'bg-yellow-500' : 'bg-green-500');
                        ?>
                        <div class="mb-4">
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium"><?php echo $category; ?></span>
                                <span class="text-sm font-medium">₱<?php echo number_format($spent, 2); ?> / ₱<?php echo number_format($budget, 2); ?></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="<?php echo $color_class; ?> h-2.5 rounded-full" 
                                     style="width: <?php echo min($percentage, 100); ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Budget Chart -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Budget vs Actual</h3>
                    <canvas id="budgetChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Setting Budget -->
    <div id="budgetModal" class="fixed inset-0 flex items-center justify-center bg-gray-500 bg-opacity-50 hidden">
        <div class="bg-white p-6 rounded-lg shadow-lg w-96">
            <h2 class="text-xl font-bold mb-4">Set Budget</h2>
            <form action="budgets.php" method="POST">
                <div class="mb-4">
                    <label for="category" class="block text-sm text-gray-600 mb-1">Category</label>
                    <select name="category" id="category" class="w-full px-4 py-2 border rounded-lg" required>
                        <?php while($category = $categories->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($category['category']); ?>">
                                <?php echo htmlspecialchars($category['category']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="amount" class="block text-sm text-gray-600 mb-1">Budget Amount</label>
                    <input type="number" name="amount" id="amount" step="0.01" min="0" 
                           class="w-full px-4 py-2 border rounded-lg" required>
                </div>
                <div class="flex justify-between">
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                        Set Budget
                    </button>
                    <button type="button" onclick="closeModal()" 
                            class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('budgetModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('budgetModal').classList.add('hidden');
        }

        // Initialize budget chart
        const ctx = document.getElementById('budgetChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [<?php echo "'" . implode("','", array_keys($budget_data)) . "'"; ?>],
                datasets: [
                    {
                        label: 'Budget',
                        data: [<?php echo implode(',', array_values($budget_data)); ?>],
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1
                    },
                    {
                        label: 'Actual',
                        data: [<?php 
                            $actual_values = array_map(function($category) use ($expense_data) {
                                return $expense_data[$category] ?? 0;
                            }, array_keys($budget_data));
                            echo implode(',', $actual_values);
                        ?>],
                        backgroundColor: 'rgba(239, 68, 68, 0.5)',
                        borderColor: 'rgb(239, 68, 68)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Hide toast after 5 seconds
        setTimeout(() => {
            document.getElementById('toast')?.classList.add('hidden');
        }, 5000);
    </script>
</body>
</html>