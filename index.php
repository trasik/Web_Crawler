<?php

/* RECURSIVE METHOD - SLOW
$root = "https://www.youtube.com/";
$seen = array();

function sanitizeUrl($url, $base) {
	if (substr($url, 0, 1) == "/" && substr($url, 0, 2) != "//") {
		$url = parse_url($base)["scheme"]."://".parse_url($base)["host"].$url;
	} else if (substr($url, 0, 2) == "//") {
		$url = parse_url($base)["scheme"].":".$url;
	} else if (substr($url, 0, 2) == "./") {
		$url = parse_url($base)["scheme"]."://".parse_url($base)["host"].dirname(parse_url($base)["path"]).substr($url, 1);
	} else if (substr($url, 0, 1) == "#") {
		$url = parse_url($base)["scheme"]."://".parse_url($base)["host"].parse_url($base)["path"].$url;
	} else if (substr($url, 0, 3) == "../") {
		$url = parse_url($base)["scheme"]."://".parse_url($base)["host"]."/".$url;
	} else if (substr($url, 0, 11) == "javascript:") {
		return;
	} else if (substr($url, 0, 5) != "https" && substr($url, 0, 4) != "http") {
		$url = parse_url($base)["scheme"]."://".parse_url($base)["host"]."/".$url;
	}
	return $url;
}

function getReq($url) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
  curl_setopt($curl, CURLOPT_HEADER, false);
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_REFERER, $url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/533.4 (KHTML, like Gecko) Chrome/5.0.375.125 Safari/533.4");
  $html = curl_exec($curl);
  curl_close($curl);

  $dom = new DOMDocument();
  @$dom->loadHtml($html);

  return $dom;
}

function getLinks($url, $depth) {
	global $seen;
	if (isset($seen[$url]) || $depth === 0) {
  	return;
  }
	$seen[$url] = true;

	$html = getReq($url);
	$links = $html->getElementsByTagName("a");
	foreach ($links as $link) {
		$link = $link->getAttribute("href");
		$link = sanitizeUrl($link, $url);
		getLinks($link, $depth - 1);
	}
	file_put_contents('results.txt', $url."\n", FILE_APPEND | LOCK_EX);
}

getLinks($root, 2);
*/

//FASTER ARRAY TO HOLD DATA
//MUST LIMIT DEPTH OF PARSER/CRAWLER
$root = "https://www.google.com/";
$seen = array($root);

$depth = 0;
while ($depth < 2) {
	$url = array_shift($seen);
	$seen = array_merge($seen, getLinks($url));
	$depth++;
}

function sanitizeUrl($url, $base) {
	if (substr($url, 0, 1) == "/" && substr($url, 0, 2) != "//") {
		$url = parse_url($base)["scheme"]."://".parse_url($base)["host"].$url;
	} else if (substr($url, 0, 2) == "//") {
		$url = parse_url($base)["scheme"].":".$url;
	} else if (substr($url, 0, 2) == "./") {
		$url = parse_url($base)["scheme"]."://".parse_url($base)["host"].dirname(parse_url($base)["path"]).substr($url, 1);
	} else if (substr($url, 0, 1) == "#") {
		$url = parse_url($base)["scheme"]."://".parse_url($base)["host"].parse_url($base)["path"].$url;
	} else if (substr($url, 0, 3) == "../") {
		$url = parse_url($base)["scheme"]."://".parse_url($base)["host"]."/".$url;
	} else if (substr($url, 0, 11) == "javascript:") {
		return;
	} else if (substr($url, 0, 5) != "https" && substr($url, 0, 4) != "http") {
		$url = parse_url($base)["scheme"]."://".parse_url($base)["host"]."/".$url;
	}
	return $url;
}

function getReq($url) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
  curl_setopt($curl, CURLOPT_HEADER, false);
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_REFERER, $url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/533.4 (KHTML, like Gecko) Chrome/5.0.375.125 Safari/533.4");
  $html = curl_exec($curl);
  curl_close($curl);

  $dom = new DOMDocument();
  @$dom->loadHtml($html);

  return $dom;
}

function getInfo($link) {
	$html = getReq($link);

	$title = $html->getElementsByTagName("title");
	$title = $title->item(0)->nodeValue;
	if(empty($title)) {
		return 'empty';
	}

	$description = "";
	$metas = $html->getElementsByTagName("meta");
	for ($i = 0; $i < $metas->length; $i++) {
		$meta = $metas->item($i);
		if (strtolower($meta->getAttribute("name")) == "description")
			$description = $meta->getAttribute("content");
	}
	return '{ "title": "'.trim($title).'", "description": "'.trim($description).'", "url": "'.$link.'"},';
}

function getLinks($url) {
	global $seen;
	$temp = array();

	$html = getReq($url);
	$links = $html->getElementsByTagName("a");
	foreach ($links as $link) {
		$link = $link->getAttribute("href");
		$link = sanitizeUrl($link, $url);
		if(!in_array($link, $seen)) {
			$temp[] = $link;
			$info = getInfo($link);
			if($info == 'empty') {
				array_pop($temp);
				continue;
			}
			file_put_contents('results.json', $info."\n", FILE_APPEND | LOCK_EX);
		}
	}
	return $temp;
}

?>
