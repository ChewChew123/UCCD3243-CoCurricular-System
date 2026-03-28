<?php

session_start();
session_destroy(); // destroy all login data

header("Location: login.php"); //back to login page
exit();
?>