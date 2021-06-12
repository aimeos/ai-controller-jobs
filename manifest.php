<?php

return [
	'name' => 'ai-controller-jobs',
	'depends' => [
		'aimeos-core',
	],
	'include' => [
		'controller/common/src',
		'controller/jobs/src',
	],
	'i18n' => [
		'controller/common' => 'controller/common/i18n',
		'controller/jobs' => 'controller/jobs/i18n',
	],
	'config' => [
		'config',
	],
	'template' => [
		'controller/jobs/templates' => [
			'controller/jobs/templates',
		],
	],
	'custom' => [
		'controller/jobs' => [
			'controller/jobs/src',
		],
	],
];
