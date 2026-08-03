<?php

require_once 'connect.php';

if(isset($_POST['sign_up'])){
    $email = $_POST['email'];
    $password = $_POST['password'];
    $password = md5($password);
    
     $check_username = "SELECT * FROM users WHERE email = '$email'";
     $result = $conn->query($check_username);
     if($result->num_rows > 0){
        echo "Username already exists. Please choose a different email.";
     } 
     else {
        $insert_user = "INSERT INTO users (email, password) 
                        VALUES ('$email', '$password')";
        if($conn->query($insert_user) === TRUE){
            header("Location: ./");
        } else {
            echo "Error: ".$conn->error;
        }
     }
}

if(isset($_POST['sign_in'])){
    $email = $_POST['email'];
    $password = $_POST['password'];
    $password = md5($password);
     
     $sql = "SELECT * FROM users WHERE email = '$email' and password = '$password'";
     $result = $conn->query($sql);
     if($result->num_rows > 0){
        session_start();
        $user = $result->fetch_assoc();
        $_SESSION['email'] = $email;
        $_SESSION['role'] = $user['role'];
        
        header("Location: G23/redirect.php?role=" . urlencode($user['role']));
     
     } 
     else {
        echo "Invalid email or password.";
     }

}


?>





