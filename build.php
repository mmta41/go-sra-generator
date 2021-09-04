<?php
$srcRoot = __DIR__ . '/src';
$buildRoot = __DIR__ . '/build/go-sra.phar';

$phar = new Phar($buildRoot ,
    FilesystemIterator::CURRENT_AS_FILEINFO |     	FilesystemIterator::KEY_AS_FILENAME, "go-sra.phar");

$phar->buildFromDirectory($srcRoot);

//if (file_exists($buildRoot)) {
//    unlink($buildRoot);
//}
//
//if (file_exists($buildRoot . '.gz')) {
//    unlink($buildRoot . '.gz');
//}
//
//$p = new Phar($buildRoot);
//$p->buildFromDirectory($srcRoot);

// pointing main file which requires all classes
//$p->setDefaultStub('index.php', '/');

// plus - compressing it into gzip
//$p->compress(Phar::GZ);

echo "php $buildRoot\n";
