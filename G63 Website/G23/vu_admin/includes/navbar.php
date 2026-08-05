<div class="navbar">
    <div class="logo">
        <i class="fa-solid fa-truck"></i>
        <span>G63 Fleet<br>Management</span>
    </div>
    <h1>Driver Dashboard</h1>
    <nav>
        <ul>
            <li class="<?= $currentPage == 'userlist' ? 'activepage' : '' ?>"><a href="userlist.php"><i class="fa-solid fa-users"></i>User List</a></li>
            <li class="<?= $currentPage == 'registerpage' ? 'activepage' : '' ?>"><a href="registerpage.php"><i class="fa-solid fa-user-plus"></i>Register Employee</a></li>
        </ul>
    </nav>
    <div class="logout"><a href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i>Log out</a></div>
</div>