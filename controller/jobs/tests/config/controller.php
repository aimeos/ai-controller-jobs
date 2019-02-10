<?php

return array(
	'jobs' => array(
		'product' => array(
			'export' => array(
				'location' => dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'tmp',
				'max-items' => 15,
				'max-query' => 5,
				'sitemap' => array(
					'location' => dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'tmp',
					'max-items' => 15,
					'max-query' => 5,
				),
			),
		),
		'catalog' => array(
			'export' => array(
				'sitemap' => array(
					'location' => dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'tmp',
					'max-items' => 10,
					'max-query' => 5,
				),
			),
		),
	),
);
