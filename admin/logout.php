<?php
require_once __DIR__ . '/../includes/functions.php';
logout_user(); flash('success','You have been signed out of the owner portal.'); redirect('admin/login.php');
