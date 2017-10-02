<?php

return array(
	'name' => 'ai-controller-jobs',
	'depends' => array(
		'aimeos-core',
	),
	'include' => array(
		'controller/common/src',
		'controller/jobs/src',
	),
	'i18n' => array(
		'controller/common' => 'controller/common/i18n',
		'controller/jobs' => 'controller/jobs/i18n',
	),
	'custom' => array(
		'controller/jobs' => array(
			'controller/jobs/src',
		),
		'controller/jobs/templates' => array(
			'controller/jobs/templates',
		),
	),
);
