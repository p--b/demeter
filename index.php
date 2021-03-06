<?php

$root    = '';
$mapping = ['/' => 'home', '/about' => 'about'];
$rawUri  = $_SERVER['REQUEST_URI'];
if (substr($rawUri, 0, strlen($root)) == $root)
    $uri = substr($rawUri, strlen($root));
else
    $uri = $rawUri;

require 'src/header.php';
if (isset($mapping[$uri]))
    require 'content/'.$mapping[$uri].'.php';
require 'src/footer.php';
