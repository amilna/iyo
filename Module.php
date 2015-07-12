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
	
	public $ipaddress = '127.0.0.1';
	public $ports = [1401,1402];
	public $proxyhost = false; /* new hostname to use tms without port (ex: 'tms1.amilna.com'). Make sure to set proxy on apache 
		see http://serverfault.com/questions/195611/how-do-i-redirect-subdomains-to-a-different-port-on-the-same-server
		and http://stackoverflow.com/questions/6764852/proxying-with-ssl */
	
	public $xmlServer = 'nodejs'; /* nodejs or apache */
	public $tileServer = 'nodejs'; /* nodejs or apache */
	
	public $xmlipaddress = '127.0.0.1';
	public $xmlports = [1403]; /* only used if nodejs */
	public $allowedips = ['127.0.0.1', '::1']; /* allowed ip to access xml url */
		 
	public $xmlDir = '@amilna/iyo/xml'; /* mapnik xml directory */
	public $pyFile = '@amilna/iyo/components/tilep.py'; /* tilep.py file, use tipe2.py if use mapnik2.0.0 */
	public $baseDir = '@webroot/tile'; /* basedir for output */
	public $baseUrl = '@web/tile'; /* baseurl for output */
	public $basePath = '@web'; /* baseurl for output */
	public $sslKey = '';
	public $sslCert = '';
	public $maxZoomCache = -1; /* -1 for no disk cache, range 0 -19 */
	
	public $urls = [];

    public function init()
    {
        parent::init();

        // custom initialization code goes here
    }
}
