<?php
	header('Content-Type: text/xml');
	echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" version="2.0">
<channel>
<title>Daily Tech News Show</title>
<link>http://www.dailytechnewsshow.com</link>
<itunes:author>Tom Merritt</itunes:author>
<itunes:owner>
	<itunes:name>Tom Merritt</itunes:name>
	<itunes:email>feedback at dailytechnewsshow.com</itunes:email>
</itunes:owner>
<itunes:summary>Daily Tech News Show features host Tom Merrit and various guests discussing featured news stories from the world of tech.</itunes:summary>
<description>Daily Tech News Show features host Tom Merrit and various guests discussing featured news stories from the world of tech.</description>
<image>
	<url>http://dtns.eternalsword.com/artwork.png</url>
	<height>1400</height>
	<width>1400</width>
</image>
<itunes:category text="Technology"><itunes:category text="Tech News" /></itunes:category>
<itunes:explicit>no</itunes:explicit>
<?php
function curl_get_file_contents($URL) {
	$c = curl_init();
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($c, CURLOPT_URL, $URL);
	$contents = curl_exec($c);
	curl_close($c);

	if ($contents) return $contents;
		else return FALSE;
}

/**
 * Returns the size of a file without downloading it, or -1 if the file
 * size could not be determined.
 *
 * @param $url - The location of the remote file to download. Cannot
 * be null or empty.
 *
 * @return The size of the file referenced by $url, or -1 if the size
 * could not be determined.
 */
function curl_get_file_size($url) {
	$result = -1;
	$output = array();
	exec('curl --head --location ' . $url, $output);
	foreach($output as $line) {
		if(strpos($line, 'Content-Length') !== false) {
			preg_match('/\d+/', $line, $matches);
			$result = $matches[0];
		}
	}
	return $result;
}

$dtns = curl_get_file_contents("http://www.dailytechnewsshow.com/feed/");
$xml = simplexml_load_string($dtns, 'SimpleXMLElement', LIBXML_NOCDATA);
$items = $xml->xpath("channel")[0]->xpath("item");
foreach($items as $item) {
	$title = htmlspecialchars(html_entity_decode($item->xpath("title")[0]));
	$description = htmlspecialchars(html_entity_decode($item->xpath("description")[0]));
	$pubDate = $item->xpath("pubDate")[0];
	$content = (string) $item->children("content", true)->encoded;
	$url = '';
	$dtns_dom = new DOMDocument();
	if($dtns_dom->loadHTML($content)) {
		$dtns_anchors = $dtns_dom->getElementsByTagName('a');
		foreach($dtns_anchors as $dtns_anchor) {
			$dtns_url = $dtns_anchor->getAttribute('href');
			if(strpos($dtns_url, 'archive.org') !== false && strpos($dtns_url, 'mp3') === false) {
				$archive = curl_get_file_contents($dtns_url);
				$archive_dom = new DOMDocument();
				$archive_dom->loadHTML($archive);
				$archive_anchors = $archive_dom->getElementsByTagName('a');
				foreach($archive_anchors as $archive_anchor) {
					$archive_url = $archive_anchor->getAttribute('href');
					if(strpos($archive_url, 'mp4') !== false) {
						$url = 'https://archive.org' . $archive_url;
					}
				}
			}
		}
	}
	if($url != '') {
		?>
		<item>
			<title><?php echo $title; ?></title>
			<pubDate><?php echo $pubDate; ?></pubDate>
			<itunes:summary><?php echo $description; ?></itunes:summary>
			<description><?php echo $description; ?></description>
			<enclosure url="<?php echo $url; ?>" length="<?php echo curl_get_file_size($url); ?>" type="video/mpeg" />
			<guid><?php echo $url; ?></guid>
		</item>
		<?php
	}
}
?>
</channel>
</rss>
