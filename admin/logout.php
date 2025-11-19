<?php
// Delete cookies by setting past expiry
setcookie("user_id", "", time() - 3600, "/");
setcookie("username", "", time() - 3600, "/");

header("Location: index.php");
exit;