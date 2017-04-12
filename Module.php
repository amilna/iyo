<?php
/* reguire gdal-bin postgis libmapnik2-2.0 mapnik-utils nodejs */


namespace amilna\iyo;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'amilna\iyo\controllers';
    public $userClass = 'common\models\User';//'dektrium\user\models\User';
	public $uploadDir = '@webroot/upload';
	public $uploadURL = '@web/upload';
	public $geom_col = 'the_geom';	
	public $postgis = 1.5;	
	
	public $ipaddress = '127.0.0.1';
	public $ports = [1401,1402];
	public $proxyhosts = []; /* new hostname to use tms without port (ex: ['tms1.amilna.com','tms2.amilna.com']). Make sure to set proxy on apache 
		see http://serverfault.com/questions/195611/how-do-i-redirect-subdomains-to-a-different-port-on-the-same-server
		and http://stackoverflow.com/questions/6764852/proxying-with-ssl */
	
	public $xmlServer = 'apache'; /* nodejs or apache */
	public $tileServer = 'webpy'; /* webpy or nodejs or apache, it is reccomended to use webpy */
	
	public $xmlipaddress = '127.0.0.1';
	public $xmlports = [1403]; /* only used if nodejs */
	public $xmlproxyhosts = []; /* new hostname to use tms without port (ex: ['tms3.amilna.com']) */
	public $allowedips = ['127.0.0.1', '::1']; /* allowed ip to access xml url */
		 
	public $xmlDir = '@amilna/iyo/xml'; /* mapnik xml directory */
	public $execFile = '@amilna/iyo/components/exec';
	public $pyFile = '@amilna/iyo/components/tilep.py'; /* tilep.py file, use tilep2.py if use mapnik2.0.0 */
	public $webpyFile = '@amilna/iyo/components/webtilep.py'; /* tilep.py file, use webtilep2.py if use mapnik2.0.0 */
	public $tileDir = '@webroot/tile'; /* basedir for output */
	public $tileURL = '@web/tile'; /* baseurl for output */
	public $basePath = '@web'; /* baseurl for output */
	public $sslKey = '';
	public $sslCert = '';
	public $maxZoomCache = -1; /* -1 for no disk cache, range 0 -19 */
	
	public $python = 'python';
	public $php = 'php';
	
	public $urls = [];

    public function init()
    {
        parent::init();

        // custom initialization code goes here
    }
}
