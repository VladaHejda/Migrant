<?php

// todo

if (file_exists(__DIR__ . '/.maintenance')) {
	require __DIR__ . '/maintenance.php';
	exit;
}
