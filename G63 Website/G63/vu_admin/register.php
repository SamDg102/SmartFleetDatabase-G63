<?php

require_once  '../db.php';


if(isset($_POST['sign_up'])){
   $email = $_POST['email'];
   $password = $_POST['password'];
   $password = md5($password);
   $role = $_POST['role'];

   $check_username = "SELECT * FROM user_account WHERE email = '$email'";
   $result = $conn->query($check_username);
   if($result->num_rows > 0){
      echo "Username already exists. Please choose a different email.";
   } 
   else {
      $insert_user = "INSERT INTO user_account (email, password, role) VALUES ('$email', '$password', '$role')";
      if($conn->query($insert_user) === TRUE){
         header("Location: userlist.php");
      } else {
         echo "Error: ".$conn->error;
      }
   }
}



