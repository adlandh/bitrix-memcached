<?php
/**
 * Created by PhpStorm.
 * User: dh
 * Date: 19.12.16
 * Time: 22:41
 */
return array(
    'cache' => array(
        'value' => array(
            'type' => array(
                'class_name' => '\DHCache\CacheEngineMemcached',
                'required_file' => 'lib/cacheenginememcached.php'
            ),
            /* you can use only 'hosts' or only 'socket', not both at the same time */
            'hosts' => array(
                array('127.0.0.1', '11211')
            ),
            //'socket' => '/tmp/memcached.sock',
            'sid' => $_SERVER["DOCUMENT_ROOT"].'#01'
        ),

    ),
);
?>