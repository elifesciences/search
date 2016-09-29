<?php

require_once __DIR__.'/bootstrap.php';

use eLife\Search\Kernel;

$doc = <<<'HTML'
        <!DOCTYPE html>
        <body style="font-family: sans-serif; ">
            <div style="margin: 45px auto; max-width: 550px; background-color: #EEE; padding: 50px">
            <img src="https://avatars0.githubusercontent.com/u/1777367?v=3&s=100" height="45" style="float:left; margin: 0 10px"/>
            <h1 style="line-height: 45px; float:left; margin-top: 0">
                eLife Search API
            </h1>
            <div style="clear: both;"></div>
            %s
            </div>
        </body>
HTML;

if (!file_exists(__DIR__.'/../config/local.php')) {
    $body = <<<'HTML'
        <p>To develop using the eLife Search API you need to set up a local configuration</p>
            <p>Please copy the example or add the following (including php start tag) to ./config/local.php</p>

            <pre style="margin: 0;">
                <code>
return [
    'debug' => true,
    'validate' => true,
    'api_url' => 'http://192.168.187.56:1242',
    'annotation_cache' => false,
    'elastic_url' => 'http://elife_search_elasticsearch:9200',
    'ttl' => 0,
];
                </code>
            </pre>
            <h2>Other requirements</h2>
            <ul>
                <li>Elasticsearch</li>
                <li>eLife Sciences API</li>
                <li>PHP 7.0+</li>
                <li>Gearman PECL extension (for scheduled jobs)</li>
            </ul>
HTML;
    echo sprintf($doc, $body);
} else {
    $config = include __DIR__.'/../config/local.php';
    // Start output buffer to catch anything unexpected.
    ob_start();
    // Wrap kernel.
    try {
        $kernel = new Kernel($config);

        $kernel->withApp(function ($app) use ($config) {
            $app['debug'] = $config['debug'] ?? false;
        });

        $kernel->run();
    }
    // Catch anything we can.
    catch (Throwable $t) {
        // Grab any printed warnings
        $content = ob_get_contents();
        // Clean output buffer to hide warnings.
        ob_clean();

        $content = '<p>'.$t->getMessage().'</p>'.($content ? '<h3>Warnings:</h3>'.'<p>'.$content.'</p>' : '');

        $content .= $t->getTraceAsString();
        // Print error page.
        echo sprintf($doc, '<p>'.$t->getMessage().'</p>'.($content ? '<h3>Warnings:</h3>'.'<p>'.$content.'</p>' : ''));
        // Flush back to user.
        ob_flush();
    }
}
