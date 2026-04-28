<?php

/**
 * Defines the navigation icons.
 *
 * @package Gallery
 *
 * $Id: navIcons.php 17870 2008-08-18 22:07:52Z JensT $
*/

if (!isset($gallery)) {
	exit;
}

if ($gallery->direction == 'ltr') {
	$fpImg = 'navigation/nav_first.gif';
	$ppImg = 'navigation/nav_prev.gif';
	$npImg = 'navigation/nav_next.gif';
	$lpImg = 'navigation/nav_last.gif';
}
else {
	$fpImg = 'navigation/nav_last.gif';
	$ppImg = 'navigation/nav_next.gif';
	$npImg = 'navigation/nav_prev.gif';
	$lpImg = 'navigation/nav_first.gif';
}
?>