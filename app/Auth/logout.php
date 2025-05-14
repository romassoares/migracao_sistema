<?php
require_once '../../routes/route.php';

if (isset($_SESSION['logged'])) {
    unset($_SESSION['logged']);
    session_destroy();

    redirect('auth/login');
} else {
    echo "error ao fazer o logout";
}
