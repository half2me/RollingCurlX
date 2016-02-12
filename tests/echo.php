<?php
$data = [];
$data['server'] = $_SERVER;
$data['headers'] = getallheaders();
$data['request'] = $_REQUEST;
usleep(mt_rand(1, 1000000));
echo json_encode($data);