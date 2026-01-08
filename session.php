<?php

if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();

ini_set('max_execution_time', 0);
