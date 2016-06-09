<?php

return array(
  // Not really used, but might support other JSON-friendly storage engines in the future?
  'storage' => 'elasticsearch',

  'elasticsearch' => array(
    'hosts' => array(
      'localhost:9200'
    ),
    'index' => 'datadiffs',
  ),
);
