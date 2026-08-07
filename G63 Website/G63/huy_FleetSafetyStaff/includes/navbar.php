<div class="navbar">
    <div class="logo">
        <i class="fa-solid fa-truck"></i>
        <span>G63 Fleet<br>Management</span>
    </div>

    <?php if (isset($currentPage) && (strpos($currentPage, 'safety') !== false || $currentPage == 'safety-staff')): ?>
        <h1>Safety Staff</h1>
        <nav>
            <ul>
                <li class="<?= $currentPage == 'safety-staff' ? 'activepage' : '' ?>">
                    <a href="safety-staff.php"><i class="fa-solid fa-car-burst"></i>Safety Event Log</a>
                </li>
                <li class="<?= $currentPage == 'safety-scores' ? 'activepage' : '' ?>">
                    <a href="scorepage.php"><i class="fa-solid fa-star"></i>Monthly Safety Scores</a>
                </li>
                <li class="<?= $currentPage == 'safety-coaching' ? 'activepage' : '' ?>">
                    <a href="coachingpage.php"><i class="fa-solid fa-chalkboard"></i>Coaching &amp; Retraining</a>
                </li>
                <li class="<?= $currentPage == 'safety-certs' ? 'activepage' : '' ?>">
                    <a href="certpage.php"><i class="fa-solid fa-id-card"></i>Driver Certifications</a>
                </li>
                <li class="<?= $currentPage == 'safety-assign' ? 'activepage' : '' ?>">
                    <a href="assignpage.php"><i class="fa-solid fa-truck-fast"></i>Driver &amp; Vehicle Assignment</a>
                </li>
            </ul>
        </nav>
        <div style="padding: 10px 20px; margin-top: auto;">
            <a href="workshop-staff.php" style="color: #18d600; font-size: 13px; font-weight: 500;">
                <i class="fa-solid fa-right-left"></i> Switch to Workshop
            </a>
        </div>
    <?php else: ?>
        <h1>Workshop Staff</h1>
        <nav>
            <ul>
                <li class="<?= $currentPage == 'workshop-staff' ? 'activepage' : '' ?>">
                    <a href="workshop-staff.php"><i class="fa-solid fa-screwdriver-wrench"></i>Maintenance Jobs</a>
                </li>
                <li class="<?= $currentPage == 'workshop-alerts' ? 'activepage' : '' ?>">
                    <a href="alertspage.php"><i class="fa-solid fa-bell"></i>Predictive Alerts</a>
                </li>
                <li class="<?= $currentPage == 'workshop-mechanics' ? 'activepage' : '' ?>">
                    <a href="mechanicspage.php"><i class="fa-solid fa-user-gear"></i>Mechanics &amp; Activities</a>
                </li>
                <li class="<?= $currentPage == 'workshop-parts' ? 'activepage' : '' ?>">
                    <a href="partspage.php"><i class="fa-solid fa-boxes-stacked"></i>Parts &amp; Suppliers</a>
                </li>
                <li class="<?= $currentPage == 'workshop-warranty' ? 'activepage' : '' ?>">
                    <a href="warrantypage.php"><i class="fa-solid fa-file-shield"></i>Warranty Claims</a>
                </li>
            </ul>
        </nav>
        <div style="padding: 10px 20px; margin-top: auto;">
            <a href="safety-staff.php" style="color: #18d600; font-size: 13px; font-weight: 500;">
                <i class="fa-solid fa-right-left"></i> Switch to Safety
            </a>
        </div>
    <?php endif; ?>

    <div class="logout">
        <a href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i>Log out</a>
    </div>
</div>