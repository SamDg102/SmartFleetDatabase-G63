<div class="navbar">
    <div class="logo">
        <i class="fa-solid fa-truck"></i>
        <span>G63 Fleet<br>Management</span>
    </div>

    <?php if (strpos($currentPage, 'safety') !== false): ?>
        <h1>Safety Staff</h1>
        <nav>
            <ul>
                <li class="<?= $currentPage == 'safety-staff' ? 'activepage' : '' ?>">
                    <a href="safety-staff.php"><i class="fa-solid fa-car-burst"></i>Safety Event Log</a>
                </li>
                <li><a href="#"><i class="fa-solid fa-star"></i>Monthly Safety Scores</a></li>
                <li><a href="#"><i class="fa-solid fa-chalkboard"></i>Coaching &amp; Retraining</a></li>
                <li><a href="#"><i class="fa-solid fa-id-card"></i>Driver Certifications</a></li>
                <li><a href="#"><i class="fa-solid fa-truck-fast"></i>Driver &amp; Vehicle Assignment</a></li>
            </ul>
        </nav>
    <?php else: ?>
        <h1>Workshop Staff</h1>
        <nav>
            <ul>
                <li class="<?= $currentPage == 'workshop-staff' ? 'activepage' : '' ?>">
                    <a href="workshop-staff.php"><i class="fa-solid fa-screwdriver-wrench"></i>Maintenance Jobs</a>
                </li>
                <li><a href="#"><i class="fa-solid fa-bell"></i>Predictive Alerts</a></li>
                <li><a href="#"><i class="fa-solid fa-user-gear"></i>Mechanics &amp; Activities</a></li>
                <li><a href="#"><i class="fa-solid fa-boxes-stacked"></i>Parts &amp; Suppliers</a></li>
                <li><a href="#"><i class="fa-solid fa-file-shield"></i>Warranty Claims</a></li>
            </ul>
        </nav>
    <?php endif; ?>

    <div class="logout"><a href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i>Log out</a></div>
</div>