<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $report_type = $_POST['report_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    // Save report to database
    $sql = "INSERT INTO reports (user_id, report_type, start_date, end_date) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $user_id, $report_type, $start_date, $end_date);
    
    if ($stmt->execute()) {
        $_SESSION['toast_message'] = 'Report generated successfully!';
        $_SESSION['toast_message_type'] = 'success';
    } else {
        $_SESSION['toast_message'] = 'Error generating report.';
        $_SESSION['toast_message_type'] = 'error';
    }
}

// Fetch data for the selected period (default to current month if no dates selected)
$start_date = $_POST['start_date'] ?? date('Y-m-01');
$end_date = $_POST['end_date'] ?? date('Y-m-t');

// Fetch total income and expenses
$sql_totals = "SELECT 
    (SELECT SUM(amount) FROM income WHERE user_id = $user_id AND date BETWEEN '$start_date' AND '$end_date') as total_income,
    (SELECT SUM(amount) FROM expenses WHERE user_id = $user_id AND date BETWEEN '$start_date' AND '$end_date') as total_expenses";
$totals = $conn->query($sql_totals)->fetch_assoc();

// Fetch expense categories breakdown
$sql_categories = "SELECT category, SUM(amount) as total 
                  FROM expenses 
                  WHERE user_id = $user_id 
                  AND date BETWEEN '$start_date' AND '$end_date'
                  GROUP BY category";
$categories = $conn->query($sql_categories);

// Fetch daily expenses for trend
$sql_trend = "SELECT DATE(date) as date, SUM(amount) as total 
              FROM expenses 
              WHERE user_id = $user_id 
              AND date BETWEEN '$start_date' AND '$end_date'
              GROUP BY DATE(date) 
              ORDER BY date";
$trend = $conn->query($sql_trend);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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
                    <h2 class="text-2xl font-bold sidebar-text">KwartaTally</h2>
                    <button onclick="toggleSidebar()" class="focus:outline-none">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                <nav>
                    <a href="dashboard.php" class="block py-4 px-4 hover:bg-blue-700  ">
                        <i class="fas fa-home mx-auto"></i>
                        <span class="ml-4 sidebar-text">Dashboard</span>
                    </a>
                    <a href="expenses.php" class="block py-4 px-4 hover:bg-blue-700  items-center">
                    &nbsp;<i class="fas fa-receipt mx-auto"></i>
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
            <h1 class="text-2xl font-bold mb-6">Financial Reports</h1>

            <!-- Date Range Selector -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <form method="POST" class="flex gap-4">
                    <div class="flex-1">
                        <label class="block text-sm text-gray-600 mb-1">Report Type</label>
                        <select name="report_type" class="w-full px-4 py-2 border rounded-lg">
                            <option value="expense">Expense Report</option>
                            <option value="income">Income Report</option>
                            <option value="summary">Summary Report</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm text-gray-600 mb-1">Start Date</label>
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>" 
                               class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm text-gray-600 mb-1">End Date</label>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>" 
                               class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                            Generate Report
                        </button>
                    </div>
                </form>
            </div>

            <!-- Toast Message -->
            <?php if (isset($_SESSION['toast_message'])): ?>
                <div id="toast" class="bg-<?php echo $_SESSION['toast_message_type'] === 'success' ? 'green' : 'red'; ?>-500 text-white px-4 py-2 rounded-lg mb-4">
                    <?php echo $_SESSION['toast_message']; ?>
                </div>
                <?php unset($_SESSION['toast_message'], $_SESSION['toast_message_type']); ?>
            <?php endif; ?>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="bg-green-100 rounded-full p-3 mr-4">
                            <i class="fas fa-money-bill-wave text-green-500"></i>
                        </div>
                        <div>
                            <h3 class="text-gray-500 text-sm">Total Income</h3>
                            <p class="text-2xl font-bold">₱<?php echo number_format($totals['total_income'] ?? 0, 2); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="bg-red-100 rounded-full p-3 mr-4">
                            <i class="fas fa-credit-card text-red-500"></i>
                        </div>
                        <div>
                            <h3 class="text-gray-500 text-sm">Total Expenses</h3>
                            <p class="text-2xl font-bold">₱<?php echo number_format($totals['total_expenses'] ?? 0, 2); ?></p>
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
                            <p class="text-2xl font-bold">₱<?php echo number_format(($totals['total_income'] ?? 0) - ($totals['total_expenses'] ?? 0), 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Expense Categories Chart -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Expense Categories</h3>
                    <canvas id="categoryChart"></canvas>
                </div>

                <!-- Expense Trend Chart -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Expense Trend</h3>
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
            <button id="exportBtn" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 mt-4">
            Export as Image
        </button>
        </div>
        
    </div>

    <script>
        // Initialize category chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php 
                    $labels = [];
                    $data = [];
                    while($category = $categories->fetch_assoc()) {
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

        // Initialize trend chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: [<?php 
                    $dates = [];
                    $amounts = [];
                    while($day = $trend->fetch_assoc()) {
                        $dates[] = "'" . date('M d', strtotime($day['date'])) . "'";
                        $amounts[] = $day['total'];
                    }
                    echo implode(',', $dates);
                ?>],
                datasets: [{
                    label: 'Daily Expenses',
                    data: [<?php echo implode(',', $amounts); ?>],
                    borderColor: '#4F46E5',
                    tension: 0.1
                }]
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
    <script>
    document.getElementById('exportBtn').addEventListener('click', function() {
        const reportContent = document.querySelector('.ml-64'); // Capture the main report area
        
        html2canvas(reportContent, {
            scale: 2, // Higher scale for better quality
            useCORS: true
        }).then(canvas => {
            const link = document.createElement('a');
            link.href = canvas.toDataURL('image/png');
            link.download = 'financial_report.png';
            link.click();
        }).catch(error => {
            console.error('Export failed:', error);
        });
    });
</script>
</body>
</html>