<?php

return [
	'email' => [
		'from-email' => 'root@localhost',
	],
	'fs' => [
		'adapter' => 'Standard',
		'basedir' => __DIR__ . '/../tmp',
	],
	'fs-import' => [
		'adapter' => 'Standard',
		'basedir' => __DIR__ . '/../tmp/import',
	],
	'fs-mimeicon' => [
		'adapter' => 'Standard',
		'basedir' => __DIR__ . '/../tmp/mime',
	],
];
