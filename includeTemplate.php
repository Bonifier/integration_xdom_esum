<?php
# Snippet to include template files from file system
# USAGE: [[includeTemplate? &tpl=`mytemplate/template.html`]]

$bonifierJS = '//bonifier.com.hk/shared/bonifier_muse2modx.js';
$bonifierCSS = '//bonifier.com.hk/shared/bonifier_muse2modx.css';

if ( !isset($tpl) || $tpl== "" ) return "Missing Template file!";

$tpl = $base_path .$tpl;
$rel_path = substr(substr($tpl, 0, strrpos($tpl, "/")), strlen($base_path));

$html = file_get_contents($tpl);
$dom = new DOMDocument;
$dom->loadHTML($html);
$xpath = new DOMXPath($dom);

// fix template referencing path
$srcNodes = $xpath->query('//@href|//@src|//@data-src');

foreach($srcNodes as $srcNode) {

    // sometimes we could usee modx tags link in muse
    // muse automatically convert [[ to %5B%5B and ]] to %5D%5D, plus prefix http://
    if(endsWith(trim($srcNode->nodeValue), "%5D%5D")) {
        $tmpNodePos = strpos($srcNode->nodeValue, "//");
        if($tmpNodePos !== false) {
            $tmpNodeVal = substr($srcNode->nodeValue, $tmpNodePos + 2);

            if(startsWith($tmpNodeVal, "%5B%5B")) {
                $srcNode->nodeValue = str_replace($tmpNodeVal, array("%5B%5B", "%5D%5D"), array("[[", "]]"));
            }
        }
    }

	// absolute path
	$isColon = strrpos($srcNode->nodeValue, ":") !== false;
	$isDoubleSlash = startsWith($srcNode->nodeValue, "//");
    
	// relative path
	if(!$isColon && !$isDoubleSlash) {
		$srcNode->nodeValue = joinPaths($rel_path, $srcNode->nodeValue);
	}
}

// signify modx dynamic value containers
$ctnNodes = $xpath->query('//h1|//h2|//h3|//h4|//h5|//h6|//p');

foreach($ctnNodes as $ctnNode) {
    if(startsWith(trim($ctnNode->nodeValue), "[[") && 
        endsWith(trim($ctnNode->nodeValue), "]]")) {

        $cssClass = $ctnNode->getAttribute('class');
        $cssClass = (trim($cssClass) != '') ? trim($cssClass) . " modx" : "modx";
        $ctnNode->setAttribute('class', $cssClass); 
    }
}

// bespoke de bonifier add on scripts
$headNode = $xpath->query('//head');

$cssNode = $dom->createElement('link');
$cssNode->setAttribute('rel', 'stylesheet');
$cssNode->setAttribute('type', 'text/css');
$cssNode->setAttribute('href', $bonifierCSS);

$headNode->item(0)->appendChild($cssNode);

$bodyNode = $xpath->query('//body');

$jsNode = $dom->createElement('script');
$jsNode->setAttribute('type', 'text/javascript');
$jsNode->setAttribute('src', $bonifierJS);

$bodyNode->item(0)->appendChild($jsNode);

ob_start();
print $dom->saveHTML();
return ob_get_clean();

function startsWith($haystack, $needle)
{
    return $needle === "" || strpos($haystack, $needle) === 0;
}

function endsWith($haystack, $needle)
{
    return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
}

function joinPaths() {
    $args = func_get_args();
    $paths = array();
    foreach ($args as $arg) {
        $paths = array_merge($paths, (array)$arg);
    }

    $paths = array_map(create_function('$p', 'return trim($p, "/");'), $paths);
    $paths = array_filter($paths);
    return join('/', $paths);
}