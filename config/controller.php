<?php

return [
	'jobs' => [
		'attribute' => [
			'import' => [
				'xml' => [
					'domains' => [
						'attribute/property' => 'attribute/property',
						'media' => 'media',
						'media/property' => 'media/property',
						'price' => 'price',
						'price/property' => 'price/property',
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
						'media/property' => 'media/property',
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
						'attribute/property' => 'attribute/property',
						'catalog' => 'catalog',
						'media' => 'media',
						'media/property' => 'media/property',
						'price' => 'price',
						'price/property' => 'price/property',
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
						'media/property' => 'media/property',
						'product' => 'product',
						'text' => 'text'
					]
				]
			]
		],
	]
];
