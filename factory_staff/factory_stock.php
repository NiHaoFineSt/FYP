<?php
session_start();
include __DIR__ . '/../config.php';

// SECURITY CHECK (Ensure user is factory staff)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'factory') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// HANDLE CLEAR HISTORY (Deletes Approved or Rejected records)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_history'])) {
    $clear_stmt = $conn->prepare("DELETE FROM factory_requests WHERE status IN ('Approved', 'Rejected', 'Completed')");
    if ($clear_stmt->execute()) {
        echo "<script>alert('History cleared successfully!'); window.location.href='factory_stock.php';</script>";
    } else {
        echo "<script>alert('Failed to clear history.'); window.location.href='factory_stock.php';</script>";
    }
    $clear_stmt->close();
    exit();
}

// FETCH MATERIAL STOCK BREAKDOWN (Sums up weight per material for 'Approved' or 'Completed' status)
$stock_summary_query = "SELECT material, SUM(weight) as total_weight 
                        FROM factory_requests 
                        WHERE status IN ('Approved', 'Completed') 
                        GROUP BY material 
                        ORDER BY total_weight DESC";
$stock_summary_result = $conn->query($stock_summary_query);

// FETCH ALL REQUEST HISTORY
$history_query = "SELECT fr.id, fr.material, fr.weight, fr.status, fr.created_at, u.name as staff_name 
                 FROM factory_requests fr 
                 LEFT JOIN users u ON fr.user_id = u.user_id 
                 ORDER BY fr.created_at DESC";
$history_result = $conn->query($history_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request History & Stock | RecycleHub</title>
    <link rel="stylesheet" href="managerequest.css">
    <link rel="stylesheet" href="factory_stock.css">
    <style>
        .status-pill {
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 0.85rem;
            display: inline-block;
        }
        .status-pill.approved, .status-pill.completed {
            background-color: #d4edda;
            color: #155724;
        }
        .status-pill.pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-pill.rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        .stock-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .stock-card {
            background: #fff;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border-left: 4px solid #2d5a27;
        }
        .stock-card h4 {
            margin: 0 0 5px 0;
            color: #555;
            font-size: 0.95rem;
        }
        .stock-card .amount {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2d5a27;
        }
    </style>
</head>
<body>

    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="logo">Recycle<span>Hub</span></div>
            <nav class="side-nav">
                <a href="factorystaff.php">Incoming Requests</a>
                <a href="factory_stock.php" class="active">Request History</a>
                <div class="nav-divider"></div>
                <a href="../logout.php" class="logout">Logout</a>
            </nav>
        </aside>

        <main class="dashboard-content">
            <header class="dash-header">
                <div>
                    <h2>Factory Inventory & History</h2>
                    <p>Overview of current accepted material stock and past transfer logs.</p>
                </div>
                <form method="POST" onsubmit="return confirm('Are you sure you want to clear processed history logs?');">
                    <input type="hidden" name="clear_history" value="1">
                    <button type="submit" class="btn-primary" style="background-color: #e53e3e; border: none; padding: 10px 18px; cursor: pointer; color: white; border-radius: 6px;">Clear History</button>
                </form>
            </header>

            <!-- CURRENT ACCEPTED STOCK CARDS -->
            <section class="stock-summary-section">
                <h3 style="margin-bottom: 15px;">Accepted Material Inventory</h3>
                <div class="stock-cards-grid">
                    <?php if ($stock_summary_result && $stock_summary_result->num_rows > 0): ?>
                        <?php while ($stock = $stock_summary_result->fetch_assoc()): ?>
                            <div class="stock-card">
                                <h4><?= htmlspecialchars($stock['material']) ?></h4>
                                <div class="amount"><?= number_format($stock['total_weight'], 1) ?> kg</div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="stock-card" style="border-left-color: #ccc;">
                            <h4>No Stock Available</h4>
                            <div class="amount" style="color: #888;">0.0 kg</div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- HISTORY LOG TABLE -->
            <section class="history-table-card">
                <h3 style="margin-bottom: 15px;">Request Logs</h3>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>REQ ID</th>
                            <th>Staff Name</th>
                            <th>Material</th>
                            <th>Weight</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="history-data">
                        <?php if ($history_result && $history_result->num_rows > 0): ?>
                            <?php while ($item = $history_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('Y-m-d H:i', strtotime($item['created_at'])) ?></td>
                                    <td>#REQ-<?= htmlspecialchars($item['id']) ?></td>
                                    <td><?= htmlspecialchars($item['staff_name'] ?? 'Staff') ?></td>
                                    <td><?= htmlspecialchars($item['material']) ?></td>
                                    <td><?= number_format($item['weight'], 1) ?> kg</td>
                                    <td>
                                        <span class="status-pill <?= strtolower(htmlspecialchars($item['status'])) ?>">
                                            <?= htmlspecialchars($item['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #888; padding: 20px;">No transfer history available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

</body>
</html>