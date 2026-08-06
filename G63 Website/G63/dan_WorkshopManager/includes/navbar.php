<div class="navbar">
    <div class="logo">
        <i class="fa-solid fa-truck"></i>
        <span>G63 Fleet<br>Management</span>
    </div>
    <h1>Workshop Dashboard</h1>
    <nav>
        <ul>
            <li class="<?= $currentPage == 'dashboard' ? 'activepage' : '' ?>"><a href="dashboard.php"><i class="fa-solid fa-gauge-high"></i>Dashboard</a></li>
            <li class="<?= $currentPage == 'vehicles' ? 'activepage' : '' ?>"><a href="vehicles.php"><i class="fa-solid fa-car-side"></i>Vehicles</a></li>
            <li class="<?= $currentPage == 'maintenancejobs' ? 'activepage' : '' ?>"><a href="maintenancejobs.php"><i class="fa-solid fa-screwdriver-wrench"></i>Maintenance Jobs</a></li>
            <li class="<?= $currentPage == 'mechanics' ? 'activepage' : '' ?>"><a href="mechanics.php"><i class="fa-solid fa-user-gear"></i>Mechanics</a></li>
            <li class="<?= $currentPage == 'parts' ? 'activepage' : '' ?>"><a href="parts.php"><i class="fa-solid fa-gears"></i>Parts</a></li>
            <li class="<?= $currentPage == 'suppliers' ? 'activepage' : '' ?>"><a href="suppliers.php"><i class="fa-solid fa-warehouse"></i>Suppliers</a></li>
            <li class="<?= $currentPage == 'alerts' ? 'activepage' : '' ?>"><a href="alerts.php"><i class="fa-solid fa-triangle-exclamation"></i>Alerts</a></li>
            <li class="<?= $currentPage == 'reports' ? 'activepage' : '' ?>"><a href="reports.php"><i class="fa-solid fa-chart-line"></i>Reports</a></li>
        </ul>
    </nav>
    <div class="logout"><a href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i>Log out</a></div>
</div>