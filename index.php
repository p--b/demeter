<?php
require 'src/header.php';

$mapping = ['/' => 'home', '/about' => 'about'];

if (isset($mapping[$_SERVER['REQUEST_URI']]))
    require 'content/'.$mapping[$_SERVER['REQUEST_URI']].'.php';

require 'src/footer.php';
