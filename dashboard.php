<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch total expenses
$sql_expenses = "SELECT SUM(amount) as total_expenses FROM expenses WHERE user_id = $user_id AND MONTH(date) = MONTH(CURRENT_DATE())";
$result_expenses = $conn->query($sql_expenses);
$total_expenses = $result_expenses->fetch_assoc()['total_expenses'] ?? 0;

// Fetch total income
$sql_income = "SELECT SUM(amount) as total_income FROM income WHERE user_id = $user_id AND MONTH(date) = MONTH(CURRENT_DATE())";
$result_income = $conn->query($sql_income);
$total_income = $result_income->fetch_assoc()['total_income'] ?? 0;

// Fetch recent transactions
$sql_recent = "SELECT 'expense' as type, amount, category, date FROM expenses WHERE user_id = $user_id 
               UNION ALL 
               SELECT 'income' as type, amount, source as category, date FROM income WHERE user_id = $user_id 
               ORDER BY date DESC LIMIT 5";
$recent_transactions = $conn->query($sql_recent);

// Fetch expense categories for the chart
$sql_categories = "SELECT category, SUM(amount) as total FROM expenses 
                  WHERE user_id = $user_id AND MONTH(date) = MONTH(CURRENT_DATE())
                  GROUP BY category";
$expense_categories = $conn->query($sql_categories);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            <!-- Top Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="bg-green-100 rounded-full p-3 mr-4">
                            <i class="fas fa-wallet text-green-500"></i>
                        </div>
                        <div>
                            <h3 class="text-gray-500 text-sm">Monthly Income</h3>
                            <p class="text-2xl font-bold">₱<?php echo number_format($total_income, 2); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="bg-red-100 rounded-full p-3 mr-4">
                            <i class="fas fa-credit-card text-red-500"></i>
                        </div>
                        <div>
                            <h3 class="text-gray-500 text-sm">Monthly Expenses</h3>
                            <p class="text-2xl font-bold">₱<?php echo number_format($total_expenses, 2); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="bg-blue-100 rounded-full p-3 mr-4">
                            <i class="fas fa-coins text-blue-500"></i>
                        </div>
                        <div>
                            <h3 class="text-gray-500 text-sm">Net Balance</h3>
                            <p class="text-2xl font-bold">₱<?php echo number_format($total_income - $total_expenses, 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts and Recent Transactions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Expense Chart -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Expense Distribution</h3>
                    <canvas id="expenseChart"></canvas>
                </div>

                <!-- Recent Transactions -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Recent Transactions</h3>
                    <div class="space-y-4">
                        <?php while($transaction = $recent_transactions->fetch_assoc()): ?>
                            <div class="flex items-center justify-between border-b pb-2">
                                <div class="flex items-center">
                                    <div class="<?php echo $transaction['type'] == 'income' ? 'bg-green-100' : 'bg-red-100' ?> rounded-full p-2 mr-3">
                                        <i class="fas <?php echo $transaction['type'] == 'income' ? 'fa-arrow-down text-green-500' : 'fa-arrow-up text-red-500' ?>"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold"><?php echo ucfirst($transaction['category']); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo date('M d, Y', strtotime($transaction['date'])); ?></p>
                                    </div>
                                </div>
                                <p class="<?php echo $transaction['type'] == 'income' ? 'text-green-500' : 'text-red-500' ?> font-semibold">
                                    <?php echo $transaction['type'] == 'income' ? '+' : '-'; ?>₱<?php echo number_format($transaction['amount'], 2); ?>
                                </p>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize expense chart
        const ctx = document.getElementById('expenseChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [<?php 
                    $labels = [];
                    $data = [];
                    while($category = $expense_categories->fetch_assoc()) {
                        $labels[] = "'" . $category['category'] . "'";
                        $data[] = $category['total'];
                    }
                    echo implode(',', $labels);
                ?>],
                datasets: [{
                    data: [<?php echo implode(',', $data); ?>],
                    backgroundColor: [
                        '#4F46E5',
                        '#7C3AED',
                        '#EC4899',
                        '#F59E0B',
                        '#10B981'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>