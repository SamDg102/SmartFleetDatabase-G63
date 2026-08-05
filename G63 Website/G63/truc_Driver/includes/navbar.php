<div class="navbar">
    <div class="logo">
        <i class="fa-solid fa-truck"></i>
        <span>G63 Fleet<br>Management</span>
    </div>
    <h1>Driver Dashboard</h1>
    <nav>
        <ul>
            <li class="<?= $currentPage == 'scorepage' ? 'activepage' : '' ?>"><a href="scorepage.php"><i class="fa-solid fa-star"></i>Monthly Safety Score</a></li>
            <li class="<?= $currentPage == 'incidentpage' ? 'activepage' : '' ?>"><a href="incidentpage.php"><i class="fa-solid fa-car-burst"></i>Incident History</a></li>
            <li class="<?= $currentPage == 'vehiclepage' ? 'activepage' : '' ?>"><a href="vehiclepage.php"><i class="fa-solid fa-briefcase"></i>Assigned Vehicle</a></li>
            <li class="<?= $currentPage == 'trainingpage' ? 'activepage' : '' ?>"><a href="trainingpage.php"><i class="fa-solid fa-chalkboard"></i>Training Course</a></li>
        </ul>
    </nav>
    <div class="logout"><a href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i>Log out</a></div>
</div>