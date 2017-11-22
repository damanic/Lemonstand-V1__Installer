<?php
$repository_info = array(
	'name'         => 'Core Module Updates | github:damanic',
	'description'  => 'Security & Bug Fixes to core modules no longer updated by lemonstand (cms,core,etc). Plus updates to the updatecenter module',
	'repositories' => array(
		array(
			'source'  => 'github',
			'modules' => array(
				'core'         => array(
					'owner'                => 'damanic',
					'repo'                 => 'ls1-module-core',
					'default_allow_update' => true,
					'view_info_url'        => 'https://github.com/damanic/ls1-module-core/blob/master/readme.md',
					'view_releases_url'    => 'https://github.com/damanic/ls1-module-core/releases',
				),
				'system'       => array(
					'owner'                => 'damanic',
					'repo'                 => 'ls1-module-system',
					'default_allow_update' => true,
				),
				'backend'      => array(
					'owner'                => 'damanic',
					'repo'                 => 'ls1-module-backend',
					'default_allow_update' => true,
					'view_info_url'        => 'https://github.com/damanic/ls1-module-backend/blob/master/readme.md',
					'view_releases_url'    => 'https://github.com/damanic/ls1-module-backend/releases',
				),
				'cms'          => array(
					'owner'                => 'damanic',
					'repo'                 => 'ls1-module-cms',
					'default_allow_update' => true,
					'view_info_url'        => 'https://github.com/damanic/ls1-module-cms/blob/master/readme.md',
					'view_releases_url'    => 'https://github.com/damanic/ls1-module-cms/releases',
				),
				'shop'         => array(
					'owner'                => 'damanic',
					'repo'                 => 'ls1-module-shop',
					'default_allow_update' => true,
					'view_info_url'        => 'https://github.com/damanic/ls1-module-shop/blob/master/readme.md',
					'view_releases_url'    => 'https://github.com/damanic/ls1-module-shop/releases',
				),
				'blog'         => array(
					'owner'                => 'damanic',
					'repo'                 => 'ls1-module-blog',
					'default_allow_update' => true,
				),
				'updatecenter' => array(
					'owner'                => 'damanic',
					'repo'                 => 'ls1-module-updatecenter',
					'default_allow_update' => true,
					'view_info_url'        => 'https://github.com/damanic/ls1-module-updatecenter/blob/master/readme.md',
					'view_releases_url'    => 'https://github.com/damanic/ls1-module-updatecenter/releases',
				),

			)
		)
	),
);