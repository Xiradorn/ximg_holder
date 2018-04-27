<?php

require 'ximg_class.php';

use \XImg\XImgClass;

$x = new XImgClass();

if (isset($_GET)) {
	$x->rendering($_GET);
} else {
	$x->rendering();
}
