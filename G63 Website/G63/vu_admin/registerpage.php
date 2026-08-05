<?php
session_start();

require_once 'includes/conndb.php';
include '../function.php';

checkin('admin');
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="description" content="Driver dashboard of G63 Fleet Managment System">
        <meta name="keywords" content="Driver, Fleet, Vehicle">
        <meta name="author" content="Dang Nguyen Xuan Truc">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
        <title>G63 Driver Dashboard</title>
    
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;500;700&display=swap">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <link rel="stylesheet" href="styles/navbar.css">
        <link rel="stylesheet" href="styles/registerpage.css">
    </head>

    <body>
        <?php 
        $currentPage = 'registerpage';
        include 'includes/navbar.php';
        ?>

        <main class="registerpage">
            <header>
                <h1>Register New Employees</h1>
                <hr>
            </header>
            
            <section class="container" id="sign_up">
                <form action="register.php" method="POST">
                    <div class="input-group">
                        <input type="email" id="email" name="email" required >
                        <label for="email">Email</label>
                    </div>
                    <div class="input-group">
                        <input type="password" id="password" name="password" required >
                        <label for="password">Password</label>
                    </div>
                    <div class="input-group">
                        <select id="role"  name="role" required>
                            <option value="driver">Driver</option>
                            <option value="fleet safety staff">Fleet Safety Staff</option>
                            <option value="workshop manager">Workshop Manager</option>
                            <option value="mechanic">Mechanic</option>
                        </select>
                        <label for="role">Role</label>
                    </div>
                    <input type="submit" name="sign_up" value="sign_up">
                </form>
            </section>
            <footer>
                <p>&copy; 2026 G63 Fleet Management System. All rights reserved.</p>
            </footer>
        </main>

    </body>
