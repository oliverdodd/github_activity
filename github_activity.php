<?php
/*
Plugin Name: github_activity
Plugin URI: http://wordpress.org/#
Description: list recent activity on your github account
Author: Oliver C Dodd
Version: 1.0.0
Author URI: http://01001111.net
  
  Copyright (c) 2009 Oliver C Dodd - http://01001111.net
  
  Much of the functionality is taken from the free 01001111 library
  
  *NOTE: your calendar must be publicly viewable
  
  Permission is hereby granted,free of charge,to any person obtaining a 
  copy of this software and associated documentation files (the "Software"),
  to deal in the Software without restriction,including without limitation
  the rights to use,copy,modify,merge,publish,distribute,sublicense,
  and/or sell copies of the Software,and to permit persons to whom the 
  Software is furnished to do so,subject to the following conditions:
  
  The above copyright notice and this permission notice shall be included in
  all copies or substantial portions of the Software.
  
  THE SOFTWARE IS PROVIDED "AS IS",WITHOUT WARRANTY OF ANY KIND,EXPRESS OR
  IMPLIED,INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL 
  THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,DAMAGES OR OTHER
  LIABILITY,WHETHER IN AN ACTION OF CONTRACT,TORT OR OTHERWISE,ARISING
  FROM,OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
  DEALINGS IN THE SOFTWARE.
*/
class github_activity {
	/*-VARIABLES----------------------------------------------------------*/
	public $title;
	private $user;
	private $limit;
	private $dateFormat;
	private $divid;
	
	const option_key = "github_activity";
	
	/*-CONSTRUCT----------------------------------------------------------*/
	//public function __construct($u,$t="github_activity",$d="")
	public function github_activity($options) {
		foreach($options as $k => $v)
			@($this->$k = $v);
	}
	
	private function url() {
		return "http://github.com/$this->user.atom";
	}
	
	/*-GET OPTIONS--------------------------------------------------------*/
	public static function getOptions() {
		return !($options = get_option(github_activity::option_key))
			? $options = array(
				'user'		=> "php",
				'title'		=> "github activity",
				'limit'		=> "",
				'dateFormat'	=> "Y-m-d H:i:s:",
				'divid'		=> "github_activity")
			: $options;
	}
	
	/*-OUTPUT EVENTS------------------------------------------------------*/
	public function outputEvents()
	{
		$limit = is_numeric($this->limit) ? $this->limit : -1;
		$entries = FeedReader::latestN($this->url(),$limit);
		$html = "";
		foreach ($entries as $e) {
			$date = date($this->dateFormat,strtotime($e->date));
			$html .= "
			<div>	<span class='postDate'>$date</span>
				<a class='postTitle' href='$e->link' target='_blank'>$e->title</a>
			</div>";
		}
		return $html;
	}
}
/*-FEED-----------------------------------------------------------------------*/
class FeedEntry { public $feed; public $title; public $date; public $link; public $content; }
class Feed { public $title; public $link; public $entries; }
class FeedEntryType extends FeedEntry { public function __construct() {} public static function initWithArray($a=array()) { $o = new self(); foreach ($a as $k => $v) $o->$k = $v; return $o; } }
class FeedType extends Feed { public static $types = array(); public $rootTag; public $entryTag; public function __construct() {} public static function initWithArray($a=array()) { $o = new self(); foreach ($a as $k => $v) $o->$k = $v; return $o; } public static function detect($doc) { foreach(self::$types as $id => $type) { if ($doc->getElementsByTagName($type->rootTag)->length > 0) { return $type; } } } } FeedType::$types['atom'] = FeedType::initWithArray(array( 'rootTag' => 'feed', 'title' => 'title', 'link' => array('tag' => 'link', 'attributes' => array('rel' => 'alternate'), 'get' => 'href'), 'entryTag' => 'entry', 'entries' => FeedEntryType::initWithArray(array( 'title' => 'title', 'date' => 'published', 'link' => array('tag' => 'link', 'attributes' => array('rel' => 'alternate'), 'get' => 'href'), 'content' => 'content' )) )); FeedType::$types['rss2'] = FeedType::initWithArray(array( 'rootTag' => 'channel', 'title' => 'title', 'link' => 'link', 'entryTag' => 'item', 'entries' => FeedEntryType::initWithArray(array( 'title' => 'title', 'date' => 'pubDate', 'link' => 'link', 'content' => 'description' )) ));
class FeedReader { public static function fetch($uri) { if (function_exists("curl_init") && ($ch = curl_init())) { curl_setopt($ch,CURLOPT_URL,$uri); curl_setopt($ch,CURLOPT_HEADER,false); curl_setopt ($ch,CURLOPT_RETURNTRANSFER,true); curl_setopt($ch,CURLOPT_USERAGENT,'FeedBurner/1.0 (http://www.FeedBurner.com)'); $xml = curl_exec($ch); curl_close($ch); return $xml; } else { return file_get_contents($uri); } } public static function parse($xml,$n=0) { $doc = new DOMDocument(); $doc->loadXML($xml); $feed = new Feed(); $entryFeed = new Feed(); $feedType = FeedType::detect($doc); if (!$feedType) return $feed; $node = $doc->getElementsByTagName($feedType->rootTag)->item(0); foreach ($feed as $k => $v) { if ($k == 'entries') continue; $tag = isset($feedType->$k) ? $feedType->$k : $k; $feed->$k = self::tagValue($node,$tag); $entryFeed->$k = $feed->$k; } $feed->entries = array(); foreach ($doc->getElementsByTagName($feedType->entryTag) as $node) { $entry = new FeedEntry(); $entry->feed = $entryFeed; foreach ($entry as $k => $v) { if ($k == 'feed') continue; $tag = isset($feedType->entries->$k) ? $feedType->entries->$k : $k; $entry->$k = self::tagValue($node,$tag); } $feed->entries[] = $entry; if (--$n == 0) break; } return $feed; } public static function get($uri,$n=0) { return self::parse(self::fetch($uri),$n); } public static function tagValue($node,$tag) { if (!is_array($tag)) { $items = $node->getElementsByTagName($tag); $i = 0; do { $v = @$items->item($i)->textContent; } while(!$v && $i++ < $items->length); return $v; } $tagName = $tag['tag']; $attributes = isset($tag['attributes']) ? $tag['attributes'] : array(); $tags = $node->getElementsByTagName($tagName); for ($i = 0; $i < $tags->length; $i++) { $found = true; foreach ($attributes as $k => $v) $found &= strcasecmp($tags->item($i)->getAttribute($k),$v) == 0; if ($found) { $element = $tags->item($i); break; } } return $found ? (isset($tag['get']) ? $element->getAttribute($tag['get']) : $element->textContent) : null; } public static function latestN($urls,$n=5) { if (!is_array($urls)) $urls = array($urls); $latestEach = self::latestNeach($urls,$n); $dateOrdered = array(); foreach ($latestEach as $feed) { foreach ($feed->entries as $e) { $t = strtotime($e->date).' '.$e->title; $dateOrdered[$t] = $e; } } krsort($dateOrdered); $latest = array(); $i = $n; while ($i-- && current($dateOrdered)) { $latest[] = current($dateOrdered); next($dateOrdered); } return $latest; } public static function latestNeach($urls=array(),$n=1) { if (!is_array($urls)) $urls = array($urls); $latest = array(); foreach ($urls as $url) { $latest[$url] = self::get($url,$n); } return $latest; } }
/*-OPTIONS--------------------------------------------------------------------*/
function widget_github_activity_options()
{
	$options = github_activity::getOptions();
	if($_POST['github_activity-submit'])
	{
		$options = array(
			'user'		=> $_POST['github_activity-user'],
			'title'		=> $_POST['github_activity-title'],
			'limit'		=> $_POST['github_activity-limit'],
			'dateFormat'	=> $_POST['github_activity-dateFormat'],
			'divid'		=> $_POST['github_activity-divid']
		);
		update_option(github_activity::option_key,$options);
	}
	?>
	<p>	Github User ID:
		<input	type="text"
			name="github_activity-user"
			id="github_activity-user"
			value="<?php echo $options['user']; ?>"  />
	</p>
	<p>	Title:
		<input	type="text"
			name="github_activity-title"
			id="github_activity-title"
			value="<?php echo $options['title']; ?>"  />
	</p>
	<p>	Limit Events (blank for no limit):
		<input	type="text"
			name="github_activity-limit"
			id="github_activity-limit"
			value="<?php echo $options['limit']; ?>"  />
	</p>
	<p>	Date Format (see php's <a href="http://www.php.net/manual/en/function.date.php">date()</a> function for reference):
		<input	type="text"
			name="github_activity-dateFormat"
			id="github_activity-dateFormat"
			value="<?php echo $options['dateFormat']; ?>"  />
	</p>
	<p>	Wrapper Div ID (blank for no div):
		<input	type="text"
			name="github_activity-divid"
			id="github_activity-divid"
			value="<?php echo $options['divid']; ?>"  />
	</p>
	<input type="hidden" id="github_activity-submit" name="github_activity-submit" value="1" />
	<?php
}
/*-WIDGETIZE------------------------------------------------------------------*/
function widget_github_activity_init()
{
	if (!function_exists('register_sidebar_widget')) { return; }
	function widget_github_activity($args) {
		extract($args);
		$options = github_activity::getOptions();
		$w = new github_activity($options);
		echo "	$before_widget
			$before_title $w->title $after_title
				{$w->outputEvents()}
			$after_widget
		";
	}
	register_sidebar_widget('github_activity','widget_github_activity');
	register_widget_control('github_activity','widget_github_activity_options');
}
add_action('plugins_loaded', 'widget_github_activity_init');
?>
