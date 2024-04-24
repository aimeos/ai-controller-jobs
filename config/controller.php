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
		'media' => [
			'extensions' => [
				'application/pdf' => 'pdf',
				'application/postscript' => 'ps',
				'application/vnd.ms-excel' => 'xls',
				'application/vnd.ms-powerpoint' => 'ppt',
				'application/vnd.ms-word' => 'doc',
				'application/vnd.oasis.opendocument.graphics' => 'odg',
				'application/vnd.oasis.opendocument.presentation' => 'odp',
				'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
				'application/vnd.oasis.opendocument.text' => 'odt',
				'application/epub+zip' => 'epub',
				'application/x-gzip' => 'gz',
				'application/zip' => 'zip',
				'image/bmp' => 'bmp',
				'image/gif' => 'gif',
				'image/jpeg' => 'jpg',
				'image/png' => 'png',
				'image/svg+xml' => 'svg',
				'image/tiff' => 'tif',
				'image/webp' => 'webp',
				'text/csv' => 'csv',
				'video/mp4' => 'mp4',
				'video/webm' => 'webm',
				'audio/mpeg' => 'mpeg',
				'audio/ogg' => 'ogg',
				'audio/webm' => 'weba',
			],
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
