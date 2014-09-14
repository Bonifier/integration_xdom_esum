<?php
# Snippet to include template files from file system
# USAGE: [[includeTemplate? &tpl=`assets\_bespoke\tp_pg1.html`              // muse html file load as template 
#                           &component1=`assets\_bespoke\tp_accordion.html`  // muse html file load as component
#                           &placehold1=`u1391-2`                           // element id in !template! for the component to add in
#                           &repeatId1=`u1587`                              // element id in !component! which needed to repeat
#                           &repeatRef1=`1,3,4,7`                           // modx resources id for repeater to refer to
#                           &component2=`assets\_bespoke\tp_menu.html` ...]] // goes on

// check parameters integrity
if ( !isset($tpl) || $tpl== "" ) return "Missing Template file!";

// and count how many effective templates defined
$paramsMandate = array('component', 'placehold');
$templateCnt = -1;
for($i = 1; true; $i++)
    foreach ($paramsMandate as $param) {
        if(!isset(${$param . $i}) || ${$param . $i} == "") {
            $templateCnt = $i - 1;
            break;
        }
    }
    if($templateCnt != -1) break;
}

// load the whole template html to dom tree & xpath
$rel_path = substr($tpl, 0, strrpos($tpl, "/"));

$html = file_get_contents($tpl);
$dom = new DOMDocument;
$dom -> loadHTML($html);
$xpath = new DOMXPath($dom);

// load components
for($i = 1; $i <= $templateCnt; $i++) {

    $html_com = file_get_contents(${'component' . $i - 1});
    $dom_com = new DOMDocument;
    $dom_com -> loadHTML($html_com);
    $xpath_com = new DOMXPath($dom_com);

    // merge body (everything excl' script)

        // handle repeat

    // merge head (meta + script) - skip duplicate (rough check)

    // merge head () - skip duplicate (rough check)
}

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
$bonifierJS = '//bonifier.com.hk/shared/bonifier_muse2modx.js';
$bonifierCSS = '//bonifier.com.hk/shared/bonifier_muse2modx.css';

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