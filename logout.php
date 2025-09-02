<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/app/controllers/AuthController.php';
// clear DB current_session if logged in
if (!empty($_SESSION['user_id'])) {
	require_once __DIR__ . '/app/models/User.php';
	try {
		User::clearCurrentSession((int)$_SESSION['user_id']);
	} catch (Exception $e) {
		// ignore
	}
}

AuthController::logout();
header('Location: ' . (BASE_PATH ?: '') . '/login?msg=Anda+telah+logout');
exit();
