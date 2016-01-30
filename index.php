<?php
require 'src/header.php';

$root    = '';
$mapping = ['/' => 'home', '/about' => 'about'];

if (isset($mapping[$req = $root.$_SERVER['REQUEST_URI']]))
    require 'content/'.$mapping[$req].'.php';

require 'src/footer.php';
