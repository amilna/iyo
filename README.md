IYO
===
Integration of Yii and OpenLayers

Require
-------
gdal-bin, postgis, proj, libmapnik2, mapnik-utils, nodejs

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
		/* 'userClass' => 'dektrium\user\models\User', // example if use another user class */
	],
	'iyo' => [
        'class' => 'amilna\iyo\Module',
        'userClass' =>  'dektrium\user\models\User',//'common\models\User',
        'geom_col'=>'the_geom'
        /* see vendor/amilna/yii2-iyo/Module.php for more options */ 
    ],
```

Usage
-----

Once the extension is installed, check the url:
[your application base url]/index.php/iyo

To Do
-----
1. Create interactive layer & map settings


