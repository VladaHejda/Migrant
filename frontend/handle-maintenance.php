<?php

namespace VladaHejda\Migrant;

require_once __DIR__ . '/../vendor/autoload.php';

try {
	RemoteRunner::run(@$_GET['operation'], @$_GET['password']);
} catch (\Exception $e) {
	header(' ', null, $e instanceof RequestException ? 403 : 400);
	echo $e->getMessage() . "\n";
}
