<?php
define("__ROOT__", __DIR__ . "/../../../");
require_once(__DIR__ . "/../../library/logger.php");
require_once(__DIR__ . "/../../library/public/external.php");

use Public\External\Control;

control::get($_GET["fileLocation"], __DIR__);