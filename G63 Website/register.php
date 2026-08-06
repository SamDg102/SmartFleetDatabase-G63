<?php

require_once 'connect.php';

if(isset($_POST['sign_in'])){
    $email = $_POST['email'];
    $password = $_POST['password'];
    $password = md5($password);
     
     $sql = "SELECT * FROM user_account WHERE email = '$email' and password = '$password'";
     $result = $conn->query($sql);
     if($result->num_rows > 0){
        session_start();
        $user = $result->fetch_assoc();
        $_SESSION['email'] = $email;
        $_SESSION['role'] = $user['role'];
        
        header("Location: G63/redirect.php?role=" . urlencode($user['role']));
     
     } 
     else {
        echo "Invalid email or password.";
     }

}


?>





