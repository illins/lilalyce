<?php

$dir = '/tmp';
if(is_dir($dir)) {
  $file_list = scandir($dir);
  
  $now = \DateTime::createFromFormat('Y-m-d H:i:s', date("Y-m-d H:i:s"));
  foreach ($file_list as $filename) {
    if($filename != "test.txt") {
      continue;
    }
    
    $file = sprintf("%s/%s", $dir, $filename);
    $filetime = \DateTime::createFromFormat('Y-m-d H:i:s', date("Y-m-d H:i:s", filemtime($file)));
    
    // Get the difference.
    $diff = $now->diff($filetime);
    
    // Delete the file if older than 30 minutes.
    $minutes = $diff->days * 25 * 60 + $diff->h * 60 + $diff->i;
    if($minutes > 30) {
      unlink($file);
    }
  }
}