<?php
// master_list.php (Branch Directory)
include 'config.php';

// Security Check
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['SUPER ADMIN', 'ADMIN', 'MANAGER'])){
    header("Location: login.php");
    exit();
}

// Fetch all distinct locations and their lot statistics
$branches = [];
$sql = "SELECT 
            COALESCE(location, 'Unassigned') as branch_name,
            COUNT(id) as total_lots,
            SUM(CASE WHEN status = 'AVAILABLE' THEN 1 ELSE 0 END) as available_lots,
            SUM(CASE WHEN status = 'RESERVED' THEN 1 ELSE 0 END) as reserved_lots,
            SUM(CASE WHEN status = 'SOLD' THEN 1 ELSE 0 END) as sold_lots
        FROM lots 
        WHERE location IS NOT NULL AND location != ''
        GROUP BY location
        ORDER BY location ASC";

$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch Directory | JEJ Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            var(--primary): #2e7d32; var(--gray-border): #c8e6c9; var(--text-muted): #607d8b; var(--dark): #1b5e20;
        }
        body { background-color: #f8fafc; display: flex; min-height: 100vh; font-family: 'Inter', sans-serif; margin: 0; }
        
        /* Sidebar (Minified for brevity, same as your original) */
        .sidebar { width: 260px; background: #ffffff; border-right: 1px solid #e2e8f0; position: fixed; height: 100vh; z-index: 100; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .brand-box { padding: 25px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 12px; }
        .sidebar-menu { padding: 20px 15px; }
        .menu-link { display: flex; align-items: center; gap: 12px; padding: 12px 18px; color: #475569; text-decoration: none; font-weight: 500; font-size: 14px; border-radius: 10px; margin-bottom: 6px; }
        .menu-link.active { background: #e0f2fe; color: #0284c7; font-weight: 600; }
        .main-panel { margin-left: 260px; flex: 1; padding: 0; width: calc(100% - 260px); }
        .top-header { padding: 20px 40px; background: white; border-bottom: 1px solid #e2e8f0; }
        .top-header h1 { font-size: 22px; font-weight: 800; color: #1e293b; margin: 0 0 5px 0; }
        .content-area { padding: 40px; }

        /* Branch Grid Cards */
        .branch-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }
        .branch-card { background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transition: 0.3s; cursor: pointer; text-decoration: none; display: block; position: relative; overflow: hidden; }
        .branch-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border-color: #bae6fd; }
        .branch-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: #0ea5e9; }
        
        .bc-header { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
        .bc-icon { width: 50px; height: 50px; background: #e0f2fe; color: #0284c7; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .bc-title { font-size: 18px; font-weight: 800; color: #0f172a; margin: 0; }
        .bc-subtitle { font-size: 12px; color: #64748b; font-weight: 500; }
        
        .bc-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; background: #f8fafc; padding: 15px; border-radius: 12px; margin-bottom: 20px; }
        .stat-item { display: flex; flex-direction: column; }
        .stat-val { font-size: 18px; font-weight: 700; color: #1e293b; }
        .stat-lbl { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; }
        
        .bc-action { text-align: center; color: #0284c7; font-size: 14px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .branch-card:hover .bc-action { color: #0369a1; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="brand-box">
            <img src="assets/logo.png" style="height: 38px; width: auto; border-radius: 8px;">
            <div style="line-height: 1.1;">
                <span style="font-size: 16px; font-weight: 800; color: #2e7d32;">JEJ Surveying</span>
                <span style="font-size: 11px; color: #607d8b;">Management Portal</span>
            </div>
        </div>
        <div class="sidebar-menu">
            <a href="admin.php?view=dashboard" class="menu-link"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
            <a href="master_list.php" class="menu-link active"><i class="fa-solid fa-map-location-dot"></i> Master List / Map</a>
            <a href="admin.php?view=inventory" class="menu-link"><i class="fa-solid fa-plus-circle"></i> Add Property</a>
            <a href="financial.php" class="menu-link"><i class="fa-solid fa-coins"></i> Financials</a>
        </div>
    </div>

    <div class="main-panel">
        <div class="top-header">
            <h1>Property Locations & Branches</h1>
            <p style="color:#64748b; margin:0; font-size:14px;">Select a municipality to view its master list, upload scheme maps, and manage properties.</p>
        </div>

        <div class="content-area">
            <div class="branch-grid">
                <?php foreach($branches as $branch): ?>
                <a href="branch_map.php?location=<?= urlencode($branch['branch_name']) ?>" class="branch-card">
                    <div class="bc-header">
                        <div class="bc-icon"><i class="fa-solid fa-map-location"></i></div>
                        <div>
                            <h3 class="bc-title"><?= htmlspecialchars($branch['branch_name']) ?></h3>
                            <span class="bc-subtitle">Branch / Municipality</span>
                        </div>
                    </div>
                    <div class="bc-stats">
                        <div class="stat-item">
                            <span class="stat-val"><?= $branch['total_lots'] ?></span>
                            <span class="stat-lbl">Total Lots</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-val" style="color: #10b981;"><?= $branch['available_lots'] ?></span>
                            <span class="stat-lbl">Available</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-val" style="color: #f59e0b;"><?= $branch['reserved_lots'] ?></span>
                            <span class="stat-lbl">Reserved</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-val" style="color: #ef4444;"><?= $branch['sold_lots'] ?></span>
                            <span class="stat-lbl">Sold</span>
                        </div>
                    </div>
                    <div class="bc-action">
                        Open Branch Map <i class="fa-solid fa-arrow-right"></i>
                    </div>
                </a>
                <?php endforeach; ?>

                <?php if(empty($branches)): ?>
                    <p style="color: #64748b;">No property locations found. Add properties to generate branches.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
