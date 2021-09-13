<?php
$baseDir = realpath(__DIR__ . "/../../../../../../../../");
return [
    // root_dir
    'eoffice_install_dir'   => $baseDir,
    'export_path'           => '/www/eoffice10/server/public/eoffice-case/',
    'export_url'            => '/eoffice10/server/public/eoffice-case/',
    'mysql_data_dir'        => $baseDir . "/mysql/data/",
    'im_proxy_config_dir'   => $baseDir . "/nginx/conf/cases/",
    'pm2_config_dir'        => $baseDir . "/pm2/",
    'pm2_config_file'       => $baseDir . "/pm2/processes.json",
];