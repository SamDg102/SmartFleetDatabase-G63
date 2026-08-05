<div class="navbar">
    <div class="logo">
        <i class="fa-solid fa-truck"></i>
        <span>G63 Fleet<br>Management</span>
    </div>
    <h1>Driver Dashboard</h1>
    <nav>
        <ul>
            <li class="<?= $currentPage == 'mechanic' ? 'activepage' : '' ?>"><a href="mechanic.php"><i class="fa-solid fa-wrench"></i>Mechanic Dashboard</a></li>
            <li class="<?= $currentPage == 'jobs' ? 'activepage' : '' ?>"><a href="jobs.php"><i class="fa-solid fa-bolt"></i>Assigned Jobs</a></li>
        </ul>
    </nav>
    <div class="logout"><a href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i>Log out</a></div>
</div>