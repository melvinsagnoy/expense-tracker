<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch income with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Get total number of income entries
$total_query = "SELECT COUNT(*) as count FROM income WHERE user_id = $user_id";
$total_result = $conn->query($total_query);
$total_income = $total_result->fetch_assoc()['count'];
$total_pages = ceil($total_income / $items_per_page);

// Fetch income for current page
$sql = "SELECT * FROM income WHERE user_id = $user_id 
        ORDER BY date DESC LIMIT $offset, $items_per_page";
$income_entries = $conn->query($sql);

// Fetch income sources for filter
$sql_sources = "SELECT DISTINCT source FROM income WHERE user_id = $user_id";
$sources = $conn->query($sql_sources);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Income Management</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
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
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Income Management</h1>
                <button onclick="window.location.href='add_income.php'" 
                        class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
                    <i class="fas fa-plus mr-2"></i>Add New Income
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <form class="flex gap-4">
                    <div class="flex-1">
                        <label class="block text-sm text-gray-600 mb-1">Source</label>
                        <select name="source" class="w-full px-4 py-2 border rounded-lg">
                            <option value="">All Sources</option>
                            <?php while($source = $sources->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($source['source']); ?>">
                                    <?php echo htmlspecialchars($source['source']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm text-gray-600 mb-1">Date Range</label>
                        <input type="date" name="start_date" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm text-gray-600 mb-1">To</label>
                        <input type="date" name="end_date" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                            <i class="fas fa-filter mr-2"></i>Apply Filters
                        </button>
                    </div>

                </form>
                <button onclick="openModal()" class="mt-4 bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
    <i class="fas fa-plus-circle mr-2"></i> Add Source
</button>
            </div>

            <!-- Modal for Adding Categories -->
<div id="addCategoryModal" class="fixed inset-0 flex items-center justify-center bg-gray-500 bg-opacity-50 hidden">
    <div class="bg-white p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-bold mb-4">Add New Income Category</h2>
        <form action="add_income_category.php" method="POST">
            <div class="mb-4">
                <label for="category_name" class="block text-sm text-gray-600 mb-1">Source Name</label>
                <input type="text" name="category_name" id="category_name" class="w-full px-4 py-2 border rounded-lg" required>
            </div>
            <div class="mb-4">
                <label for="category_description" class="block text-sm text-gray-600 mb-1">Description</label>
                <textarea name="category_description" id="category_description" class="w-full px-4 py-2 border rounded-lg" rows="3"></textarea>
            </div>
            <div class="flex justify-between">
                <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">Add Category</button>
                <button type="button" onclick="closeModal()" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600">Cancel</button>
            </div>
        </form>
    </div>
</div>

            <!-- Income Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while($income = $income_entries->fetch_assoc()): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo date('M d, Y', strtotime($income['date'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        <?php echo htmlspecialchars($income['source']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php echo htmlspecialchars($income['description']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-green-600 font-medium">
                                    +₱<?php echo number_format($income['amount'], 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="edit_income.php?id=<?php echo $income['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-900 mr-4">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete_income.php?id=<?php echo $income['id']; ?>" 
                                       onclick="return confirm('Are you sure you want to delete this income entry?')"
                                       class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                    <div class="px-6 py-4 bg-gray-50">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-500">
                                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $items_per_page, $total_income); ?> 
                                of <?php echo $total_income; ?> entries
                            </div>
                            <div class="flex gap-2">
                                <?php if($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>" 
                                       class="px-3 py-1 border rounded hover:bg-gray-100">
                                        Previous
                                    </a>
                                <?php endif; ?>
                                
                                <?php if($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>" 
                                       class="px-3 py-1 border rounded hover:bg-gray-100">
                                        Next
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Add any JavaScript functionality here
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize any necessary JavaScript features
        });
        function openModal() {
        document.getElementById('addCategoryModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('addCategoryModal').classList.add('hidden');
    }
    // Display Toast
    setTimeout(() => {
        document.getElementById('toast')?.classList.add('hidden');
    }, 5000);
    </script>
</body>
</html>