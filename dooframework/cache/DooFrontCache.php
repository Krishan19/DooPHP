<?php
/**
 * DooFrontCache class file.
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @link http://www.doophp.com/
 * @copyright Copyright &copy; 2009 Leng Sheng Hong
 * @license http://www.doophp.com/license
 */


/**
 * DooFrontCache provides frontend caching utilities.
 * <p>It can be retrieved with the shorhand Doo::cache('front'). The frontend cache supports full page and partial page caching mechanism.
 * Cache files are store in the path defined in $config['CACHE_PATH'].</p>
 *
 * <p>You can start the caching before displaying the view output.</p>
 * <code>
 * //Display cache if exist and exit the script(full page cache)
 * Doo::cache('front')->get();	
 * 
 * //Start recording and cache the page.
 * Doo::cache('front')->start();
 * $this->view()->render('index',$data);
 * Doo::cache('front')->end();
 * </code>
 *
 * <p>Partial cache can be used with the template engine:</p>
 * <code>
 * $cacheOK = Doo::cache('front')->testPart('latestuser');
 * //if cache exist, skip retrieving from DB
 * if(!$cacheOK){
 *     //get list from DB if cache not exist.
 * }
 * $this->view()->render('index',$data);
 *
 * //in the Template file
 * <!-- cache('latestuser', 60) -->
 * <li>The list of results to be loop</li>
 * <!-- endcache -->
 * </code>
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @version $Id: DooFrontCache.php 1000 2009-08-11 18:28:42
 * @package doo.cache
 * @since 1.1
 */
class DooFrontCache{
	
	private $_directory;
	private $_cachefile;
	
	public function __construct($path='') {
		if ( $path=='' ) {
			if(isset(Doo::conf()->CACHE_PATH))
				$this->_directory = Doo::conf()->CACHE_PATH . 'frontend/';
			else
				$this->_directory = Doo::conf()->SITE_PATH . 'protected/cache/frontend/';
		}else{
			$this->_directory = $path;
		}
	}
	
	public function setPath($path){
		$this->_directory = $path;
	}
	
	/**
	 * Retrieve the full page cache.
	 * @param int $secondsCache Duration till the cache expired 
	 */
	public function get($secondsCache=60){
		$uri = $_SERVER['REQUEST_URI'];
		
		if($uri[strlen($uri)-1]=='/'){
			$uri = substr($uri,0,strlen($uri)-1);
		}
		
		$this->_cachefile = $this->_directory.str_replace('/','-',$uri).'.html';
		
		// If the cache has not expired, include it.
		if (file_exists($this->_cachefile) && time() - $secondsCache < filemtime($this->_cachefile)) {
			include $this->_cachefile;
			//echo "<h1> Cached copy, generated ".date('H:i', filemtime($this->_cachefile ))." </h1>\n";
			exit;
		}
	}

	/**
	 * Retrieve the partial page cache.
	 * @param string $id ID of the partial cache.
	 * @param int $secondsCache Duration till the cache expired 
	 */
	public function getPart($id, $secondsCache=60){
		$this->_cachefile  = $this->_directory.'parts/'.$id.'.html';
		
		// If the cache has not expired, include it.
		if (file_exists($this->_cachefile) && time() - $secondsCache < filemtime($this->_cachefile)) {
			include $this->_cachefile;
			//echo "<h1> Cached loaded, generated time".date('H:i', filemtime($this->_cachefile ))." </h1>\n";
			return true;
		}
	}
	
	/**
	 * Frontend cache start. Start the output buffer.
	 * @param string $id ID of the cache. To be used with partial cache
	 */
	public function start($id=''){
		if($id!=''){
			$this->_cachefile  = $this->_directory.$id.'.html';
		}
		ob_start(); 
	}
	
	/**
	 * Frontend cache ending. Cache the output to a file in the defined cache folder.
	 */
	public function end(){
		$fp = fopen($this->_cachefile, 'w+');
		fwrite($fp, ob_get_contents());
		fclose($fp);
		ob_end_flush(); 
	}
	
	/**
	 * Check if the partial cache exists and is not expire
	 * @param string $id ID of the partial cache.
	 * @param int $secondsCache Duration till the cache expired 
	 * @return bool Returns true if the cache exists and is not yet expire.
	 */
	public function testPart($id, $secondsCache=60){
		if($id!=''){
			$this->_cachefile  = $this->_directory.$id.'.html';
			return (file_exists($this->_cachefile) && time() - $secondsCache < filemtime($this->_cachefile));
		}
		return false;
	}
	
	/**
	 * Flush full page cache file(s) based on URL.
	 * 
	 * You can flush a page cache by passing a url as defined in the routes.
	 * <code>Doo::cache('front')->flush('/blog');</code>
	 *
	 * To delete all cache in a certain URL recursively, for example:
	 * <code>
	 * // URLs in blog: /blog, /blog/article/:pname, /blog/archive/:year/:month
	 * // This will delete all cache starts with /blog
	 * Doo::cache('front')->flush('/blog', true);
	 * </code>
	 *
	 * @param string $url URL of the page cached to be deleted.
	 * @return int Number of cache file(s) deleted.
	 */
	public function flush($url, $recursive=false){
		$deleteNum=0;
		$subfolder = Doo::conf()->SUBFOLDER;
		//delete index.php without /
		if($url=='/'){
			$f1 = $this->_directory.str_replace('/','-',$subfolder).'index.php.html';
			$f2 = $this->_directory.str_replace('/','-',substr($subfolder,0,strlen($subfolder)-1)).'.html';
			if(file_exists($f1)){
				unlink( $f1 );
				$deleteNum++;
			}
			if(file_exists($f2)){
				unlink( $f2 );
				$deleteNum++;
			}
			return $deleteNum;
		}
		
		$oriUrl = $url;
		if(strpos($url,'/')===0)
			$oriUrl = $url = substr($url,1, strlen($url));
			
		$fname = $this->_directory.str_replace('/','-',$subfolder.$url).'.html';
		$fname2 = $this->_directory.str_replace('/','-',$subfolder.'index.php/'.$url).'.html';
				
		//delete cached written without index.php
		if(file_exists($fname)){
			unlink( $fname );
			$deleteNum++;
		}
			
		//delete cached written with index.php
		if(file_exists($fname2)){
			unlink( $fname2 );	
			$deleteNum++;
		}
					
		if($recursive){
			$oriUrl1 = str_replace('/','-',$subfolder.$oriUrl);
			$oriUrl2 = str_replace('/','-',$subfolder.'index.php/'.$oriUrl);
			//echo '<br><h1>'.$oriUrl1.'</h1><br>';
			//echo '<br><h1>'.$oriUrl2.'</h1><br>';
			
			$handle = opendir($this->_directory);
			while(($file = readdir($handle)) !== false) {
				if (is_file($this->_directory.$file) && (strpos($file, $oriUrl1)===0 || strpos($file, $oriUrl2)===0) ){
					unlink( $this->_directory.$file );
					$deleteNum++;
				}
			}		
		}
		return $deleteNum;
	}
	
	/**
	 * Flush partial page cache files based on ID.
	 * @param string|array $id ID name of the partial cache, you can specify an array of names
	 * @return int Number of cache file(s) deleted.
	 */
	public function flushPart($id){
		$deleteNum=0;
		if(is_string($id)){
			if(file_exists($this->_directory.'parts/'.$id.'.html')){
				unlink($this->_directory.'parts/'.$id.'.html');
				$deleteNum++;
			}
		}else{
			foreach($id as $f){
				if(file_exists($this->_directory.'parts/'.$f.'.html')){
					unlink($this->_directory.'parts/'.$f.'.html');
					$deleteNum++;
				}
			}
		}
		return $deleteNum;
	}
	
	/**
	 * Flush all frontend cache files.
	 */
	public function flushAll(){
		$this->flushAllFull();
		$this->flushAllParts();
	}
	
	/**
	 * Flush all full page cache files.
	 */
	public function flushAllFull(){
		$handle = opendir($this->_directory);
		while(($file = readdir($handle)) !== false) {
			$file = $this->_directory.$file;
			if (is_file($file)){
				unlink( $file );
			}
		}
	}
	
	/**
	 * Flush all partial page cache files.
	 */
	public function flushAllParts(){
		$handle = opendir($this->_directory.'parts/');
		while(($file = readdir($handle)) !== false) {
			$file = $this->_directory.'parts/'.$file;
			if (is_file($file)){
				unlink( $file );
			}
		}	
	}
	
}

?>