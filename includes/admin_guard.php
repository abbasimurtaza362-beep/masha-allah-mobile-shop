<?php
require_once __DIR__ . '/functions.php';
if (!current_admin_session_is_valid()) { logout_user(); flash('error', 'Administrator access is required.'); redirect('admin/login.php'); }
