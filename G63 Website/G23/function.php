<?php

function checkin ($role){
    if (!isset($_SESSION['email'])) {
        header("Location: ../");
        exit();
    }
    if ($_SESSION['role'] !== $role) {
        header("Location: ../");
        exit();
    }
}
