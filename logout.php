<?php
require_once 'config.php';

session_destroy();
flash('success', 'You have been logged out.');
redirect('login.php');
