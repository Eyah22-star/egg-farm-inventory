<?php

session_start();
session_destroy();

header("Location: /EggFarm/login.php");
exit();

?>