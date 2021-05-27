<?php

return [
	'common' => [
		'product' => [
			'import' => [
				'csv' => [
					'domains' => [
						'attribute' => 'attribute',
						'media' => 'media',
						'price' => 'price',
						'product' => 'product',
						'product/property' => 'product/property',
						'text' => 'text',
					],
				],
			],
		],
	],
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
				'xml' => [
					'domains' => [
						'attribute' => 'attribute',
						'media' => 'media',
						'price' => 'price',
						'product' => 'product',
						'product/property' => 'product/property',
						'text' => 'text'
					]
				]
			]
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
