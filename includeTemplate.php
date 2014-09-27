<?php
# Snippet to include template files from file system
# USAGE: [[includeTemplate? &tpl=`assets/_bespoke/tp_pg1.html`              // muse html file load as template 
#                           &component1=`assets/_bespoke/tp_accordion.html`  // muse html file load as component
#                           &placehold1=`u1391-2`                           // element id in !template! for the component to add in
#                           &clear1=`true`                                  // clear the placeholder before insert
#                           &width1=`100%`                                  // how the width of this component is handled
#                           &repeatId1=`u1587`                              // element id in !component! which needed to repeat
#                           &repeatRef1=`1,3,4,7`                           // modx resources id for repeater to refer to
#                           &component2=`assets/_bespoke/tp_menu.html` ...]] // goes on

// check parameters integrity
if ( !isset($tpl) || $tpl== "" ) return "Missing Template file!";

// and count how many effective templates defined
$paramsMandate = array('component', 'placehold');
$templateCnt = -1;
for($i = 1; true; $i++) {
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
$dom->loadHTML($html);
$xpath = new DOMXPath($dom);

if(!$html) {
    return "Unable to read template file " . $tpl;
}

// load components
for($i = 1; $i <= $templateCnt; $i++) {

    $html_com = file_get_contents(${'component' . $i});
    $dom_com = new DOMDocument;
    $dom_com->loadHTML($html_com);
    $xpath_com = new DOMXPath($dom_com);

    if(!$html_com) {
        return "Unable to read component file " . ${'component' . $i};
    }

    // merge body (everything inside 'page')
    // XPath Tester - http://videlibri.sourceforge.net/cgi-bin/xidelcgi
    $comNodes = $xpath_com->query("//div[@id='page']/*");
    $srcNodes = $xpath->query("//*[@id='" . ${'placehold' . $i} . "']");

    if($comNodes->length == 0) {
        return "Cannot find div#page in component file";
    } elseif($srcNodes->length == 0) {
        return "Cannot find placeholder " . ${'placehold' . $i} . " in template file";
    } else {
        // handle repeat
        if(isset(${'repeatId' . $i}) && ${'repeatId' . $i} != "" && isset(${'repeatRef' . $i}) && ${'repeatRef' . $i} != "") {

            $repNodes = $xpath_com->query("//*[@id='" . ${'repeatId' . $i} . "']", $comNodes->item(0));
            if($repNodes->length > 0) {
                foreach (preg_split("/[\r\n,]+/", ${'repeatRef' . $i}, -1, PREG_SPLIT_NO_EMPTY) as $repeatRef) {

                    // deep clone the repeating tag
                    $repNodeClone = $repNodes->item(0)->cloneNode(true);

                    // change element id
                    // $repNodeClone->setAttribute('id', $repNodeClone->getAttribute('id') . '_repId' . $repeatRef);

                    // change the {repId} to $repeatRef
                    replaceNodeValues($repNodeClone, 'repId', $repeatRef);

                    // add it to the end of repeating tags
                    $repNodes->item(0)->parentNode->appendChild($repNodeClone);
                }
            }

            // remove the seed
            $repNodes->item(0)->parentNode->removeChild($repNodes->item(0));
        }

        // insert to template
        if(!in_array($srcNodes->item(0)->nodeName, array('div', 'span'), true)) {
            // component replace placeholder (since it is not container)
            $srcNodes->item(0)->parentNode->replaceChild($dom->importNode($comNodes->item(0), true), $srcNodes->item(0));
        } else {
            // component insert to placeholder
            $strToClear = ${'clear' . $i};
            if($strToClear === 'true') {
                while ($srcNodes->item(0)->hasChildNodes()) {
                    $srcNodes->item(0)->removeChild($srcNodes->item(0)->firstChild);
                }
            }
            $srcNodes->item(0)->appendChild($dom->importNode($comNodes->item(0), true));
        }
    }

    // merge body (script) (scripts in head are not merged)
    $comNodes = $xpath_com->query("//body/script");
    $srcNodes = $xpath->query("//body/script");

    $lastSrcMatchedOrInserted = -1;
    for($j = 0; $j < $comNodes->length; $j++) {

        // compare blocks of script and sort out unmatch
        $srcMatched = false;
        for($k = 0; $k < $srcNodes->length; $k++) {
            if(isSameScripts($comNodes->item($j), $srcNodes->item($k))) {

                $lastSrcMatchedOrInserted = max($lastSrcMatchedOrInserted, $k);
                $srcMatched = true;
                break;
            }
        }

        if(!$srcMatched) {
            if($comNodes->item($j)->hasAttribute('src')) {

                // if unmatch block is just referening file, add from component to template
                $insertSrcPos = $lastSrcMatchedOrInserted + 1;
                if($insertSrcPos >= $srcNodes->length) {
                    $srcNodes->item(0)->parentNode->appendChild($dom->importNode($comNodes->item($j), true));
                } else {
                    $srcNodes->item(0)->parentNode->insertBefore($dom->importNode($comNodes->item($j), true), $srcNodes->item($insertSrcPos));
                }
            } else {

                // if unmatch block is the last in-page script block, compare line by line
                if($j == $comNodes->length - 1) {
                    $comLines = explode("\n", $comNodes->item($j)->nodeValue);
                    $srcLines = explode("\n", $srcNodes->item($srcNodes->length - 1)->nodeValue);

                    $lastLineMatchedOrInserted = -1;
                    for($l = 0; $l < count($comLines); $l++) {

                        // compare lines of script and sort out unmatch
                        $lineMatched = false;
                        for($m = 0; $m < count($srcLines); $m++) {
                            if(isSameLines($comLines[$l], $srcLines[$m])) {
                                $lastLineMatchedOrInserted = max($lastLineMatchedOrInserted, $m);
                                $lineMatched = true;
                                break;
                            }
                        }

                        // add to last match position
                        $insertLinePos = $lastLineMatchedOrInserted + 1;
                        if(!$lineMatched) {
                            if($insertLinePos >= count($srcLines)) {
                                $srcLines[] = $comLines[$l]; // append     
                            } else {
                                array_splice($srcLines, $insertLinePos, 0, $comLines[$l]);
                            }
                        }
                    }
                    $srcNodes->item($srcNodes->length - 1)->nodeValue = htmlspecialchars(implode("\n", $srcLines));
                }
            }
        }
    }

    // merge head (css) (get only the one with id=pagesheet)
    $newCss = "";
    $comWidth = ${'width' . $i};
    if(!$comWidth) {
        $comWidth = "inherit";
    }

    $comNodes = $xpath_com->query("//link[@id='pagesheet']");
    if($comNodes->length > 0) {

        $cssPath = joinPaths($rel_path, $comNodes->item(0)->getAttribute('href'));
        if(strpos($cssPath, "?") !== false) {
            $cssPath = substr($cssPath, 0, strpos($cssPath, "?"));
        }

        $css = file_get_contents($cssPath);
        if($css) {
            $inBracket = false;
            $skip = false;
            foreach(explode("\n", $css) as $comLine) {

                // copy all content except those start with #page or body
                if(!$inBracket && (startsWith($comLine, '#page') || startsWith($comLine, 'body'))) {
                    $skip = true;
                }

                if(strpos($comLine, "{") !== false) {
                    $inBracket = true;
                }

                if(strpos($comLine, "}") !== false) {
                    $inBracket = false;
                }

                if($inBracket) {
                    $comLineSplitted = explode(":", $comLine);
                    if(count($comLineSplitted) == 2) {
                        if(trim($comLineSplitted[0]) == "width") {

                            $comLine = "width: " . $comWidth . ";";
                        }

                        if(trim($comLineSplitted[0]) == "padding-left" || trim($comLineSplitted[0]) == "padding-right") {
                            // Ref: http://stackoverflow.com/questions/779434/preventing-padding-propety-from-changing-width-or-height-in-css
                            $comLine .= "-webkit-box-sizing: border-box; /* Safari/Chrome, other WebKit */";
                            $comLine .= "-moz-box-sizing: border-box;    /* Firefox, other Gecko */";
                            $comLine .= "box-sizing: border-box;         /* Opera/IE 8+ */";
                        }
                    }
                }

                if(!$skip) {
                    if($comLine) $newCss .= $comLine . "\n";
                } elseif(strpos($comLine, "}") !== false) {
                    $skip = false;
                }
            }
        }

        if($newCss) {
            $cssNode = $dom->createElement('style');
            $cssNode->nodeValue = $newCss;
            $xpath->query("//head")->item(0)->appendChild($cssNode);
        }
    }
}

// fix template referencing path
$srcNodes = $xpath->query('//@href|//@src|//@data-src');

foreach($srcNodes as $srcNode) {

    // sometimes we could use modx tags link in muse
    // muse automatically convert [[ to %5B%5B and ]] to %5D%5D, plus prefix http://
    if(endsWith(trim($srcNode->nodeValue), "%5D%5D")) {
        $tmpNodePos = strpos($srcNode->nodeValue, "//");
        if($tmpNodePos !== false) {
            $tmpNodeVal = substr($srcNode->nodeValue, $tmpNodePos + 2);

            if(startsWith($tmpNodeVal, "%5B%5B")) {
                // probably not working in XML world - will do again in last line (i.e. raw HTML print)
                $srcNode->nodeValue = str_replace(array("%5B%5B", "%5D%5D"), array("[[", "]]"), $tmpNodeVal);
            }
        }
    }

	// absolute path
    $isColon = strrpos($srcNode->nodeValue, ":") !== false;
    $isDoubleSlash = trim(startsWith($srcNode->nodeValue, "//"));
    $isModxTag = startsWith(trim($srcNode->nodeValue), "[[") && endsWith(trim($srcNode->nodeValue), "]]");
    
	// relative path
    if(!$isColon && !$isDoubleSlash && !$isModxTag) {
        $srcNode->nodeValue = joinPaths($rel_path, $srcNode->nodeValue);
    }
}

// signify modx dynamic value containers
$ctnNodes = $xpath->query('//h1|//h2|//h3|//h4|//h5|//h6|//p');

foreach($ctnNodes as $ctnNode) {
    if(startsWith(trim($ctnNode->nodeValue), "[[") && endsWith(trim($ctnNode->nodeValue), "]]")) {

        $cssClass = $ctnNode->getAttribute('class');
        $cssClass = (trim($cssClass) != '') ? trim($cssClass) . " modx" : "modx";
        $ctnNode->setAttribute('class', $cssClass); 
    }
}

// bespoke de bonifier add on scripts
$bonifierJS = '//bonifier.com.hk/shared/bonifier_muse2modx.js';
$bonifierCSS = '//bonifier.com.hk/shared/bonifier_muse2modx.css';

$headNode = $xpath->query('//head');

$baseNode = $dom->createElement('base');
$baseNode->setAttribute('href', '[[++site_url]]');

$headNode->item(0)->insertBefore($baseNode, $headNode->item(0)->firstChild);

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
// print $dom->saveHTML();
print str_replace(array("%5B%5B", "%5D%5D", "%7B", "%7D"), array("[[", "]]", "{", "}"), $dom->saveHTML());
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

function isSameLines($str1, $str2) {
    return trim($str1) === trim($str2);
}

function isSameScripts($node1, $node2) {
    if($node1->hasAttribute('src')) {
        if($node2->hasAttribute('src')) {
            return isSameLines($node1->getAttribute('src'), $node2->getAttribute('src'));
        } else {
            return false;
        }
    } else {
        return isSameLines($node1->nodeValue, $node2->nodeValue);
    }
}

function replaceNodeValues($node, $tag, $val) {

    if($node->hasAttributes()) {
        foreach ($node->attributes as $eachAttr) {
            print $eachAttr->nodeValue . "<br />";
            $eachAttr->nodeValue = str_replace(array("{".$tag."}", "%7B".$tag."%7D"), $val, $eachAttr->nodeValue);
        }
    }

    if($node->hasChildNodes()) {
        foreach ($node->childNodes as $eachChild) {
            replaceNodeValues($eachChild, $tag, $val);
        }
    } else {
        $node->nodeValue = str_replace(array("{".$tag."}", "%7B".$tag."%7D"), $val, $node->nodeValue);
    }
}