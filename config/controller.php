<?php

return [
	'jobs' => [
		'attribute' => [
			'import' => [
				'xml' => [
					'domains' => [
						'attribute/property' => 'attribute/property',
						'media' => 'media',
						'price' => 'price',
						'text' => 'text'
					]
				]
			]
		],
		'catalog' => [
			'import' => [
				'xml' => [
					'domains' => [
						'media' => 'media',
						'product' => 'product',
						'text' => 'text'
					]
				]
			]
		],
		'product' => [
			'import' => [
				'csv' => [
					'domains' => [
						'attribute' => 'attribute',
						'catalog' => 'catalog',
						'media' => 'media',
						'price' => 'price',
						'product' => 'product',
						'product/property' => 'product/property',
						'supplier' => 'supplier',
						'text' => 'text',
					],
				],
				'xml' => [
					'domains' => [
						'attribute' => 'attribute',
						'catalog' => 'catalog',
						'media' => 'media',
						'price' => 'price',
						'product' => 'product',
						'product/property' => 'product/property',
						'supplier' => 'supplier',
						'text' => 'text'
					]
				]
			]
		],
		'subscription' => [
			'process' => [
				'processors' => [
					'Email' => 'Email',
				],
			],
		],
		'supplier' => [
			'import' => [
				'xml' => [
					'domains' => [
						'supplier/address' => 'supplier/address',
						'media' => 'media',
						'product' => 'product',
						'text' => 'text'
					]
				]
			]
		],
	]
];
