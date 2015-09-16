IYO
===
Integration of Yii and OpenLayers

Require
-------
gdal-bin, postgis, proj, libmapnik2, mapnik-utils, python-webpy, python-flup, python-openssl, nodejs (optional), php5-sqlite, php5-pgsql

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Since this package do not have stable release on packagist, you should use these settings in your composer.json file :

```json
"minimum-stability": "dev",
"prefer-stable": true,
"repositories":[
		
		{
			"type": "vcs",
			"url": "https://github.com/aaiyo/yii2-kcfinder"
		}	
   ]
```
After, either run

```
php composer.phar require --prefer-dist amilna/yii2-iyo "dev-master"
```

or add

```
"amilna/yii2-iyo": "dev-master"
```

to the require section of your `composer.json` file.

run migration for database

```
./yii migrate --migrationPath=@amilna/blog/migrations
./yii migrate --migrationPath=@amilna/iyo/migrations
```

add in modules section of main config

```
	'gridview' =>  [
		'class' => 'kartik\grid\Module',
	],
	'blog' => [
		'class' => 'amilna\blog\Module',
		/* 'userClass' => 'dektrium\user\models\User', // example if use another user class, default is 'common\models\User' */
	],
	'iyo' => [
        'class' => 'amilna\iyo\Module',
        /* 'userClass' => 'dektrium\user\models\User', // example if use another user class, default is 'common\models\User' */
        'geom_col'=>'the_geom'
        /* see vendor/amilna/yii2-iyo/Module.php for more options */ 
    ],
```

add in components section of main config

```
    'components' => [        
        'errorHandler' => [
            'errorAction' => 'iyo/data/error',
        ],  
    ],

```

Set .htaccess

```
DirectoryIndex index.php

RewriteEngine on
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond $1 !^(index\.php)

RewriteRule . index.php
```

Usage
-----

Once the extension is installed, check the url:
[your application base url]/index.php/iyo

To Do
-----
1. Create interactive layer & map settings


