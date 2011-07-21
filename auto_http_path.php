<?php

function phorum_mod_auto_http_path_common_pre()
{
    global $PHORUM;

    // Do not run this code from the admin interface, otherwise the
    // HTTP Path setting would act on the generated http_path.
    if (defined('PHORUM_ADMIN')) return;

    // Keep track of the original http_path setting.
    $PHORUM["http_path_orig"] = $PHORUM["http_path"];

    // Determine absolute Phorum base URI.
    if (!empty($_SERVER["SCRIPT_URI"]))
    {
        $uri = $_SERVER['SCRIPT_URI'];

        if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] !== '')
        {
            $p = preg_quote($_SERVER['PATH_INFO'], '!');
            $uri = preg_replace(
                '!' . preg_quote($p, '!') . '(?:\?.*)?$!',
                '', $uri
            );
        }

        $PHORUM["http_path"] = preg_match('!/$!', $uri)
                             ? $uri : dirname($uri);
    }
    elseif (!empty($_SERVER["HTTP_HOST"]))
    {
        // On some systems, the port is also in the HTTP_HOST, so we
        // need to strip the port if it appears to be in there.
        if (preg_match('/^(.+):(.+)$/', $_SERVER['HTTP_HOST'], $m)) {
            $host = $m[1];
            if (!isset($_SERVER['SERVER_PORT'])) {
                $_SERVER['SERVER_PORT'] = $m[2];
            }
        } else {
            $host = $_SERVER['HTTP_HOST'];
        }

        $protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]!="off")
                  ? "https" : "http";

        $port = ($_SERVER["SERVER_PORT"]!=443 && $_SERVER["SERVER_PORT"]!=80)
              ? ':'.$_SERVER["SERVER_PORT"] : "";

        $uri = $_SERVER['REQUEST_URI'];
        if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] !== '')
        {
            $p = preg_quote($_SERVER['PATH_INFO'], '!');
            $uri = preg_replace(
                '!' . preg_quote($p, '!') . '(\?.*)?$!',
                '', $uri
            );
        }

        if ($uri[strlen($uri) - 1] != '/') {
            $dir = dirname($uri);
        } else {
            $dir = $uri;
        }

        $PHORUM["http_path"] = $protocol.'://'.$host.$port.$dir;
    }

    $PHORUM['http_path'] = preg_replace('!/$!', '', $PHORUM['http_path']);

    // Change template_http_path that one depends on the original http_path.
    if (isset($PHORUM['template_http_path'])) {
        $origq = preg_quote(preg_replace(
            '!/+$!', '', $PHORUM['http_path_orig']), '/'
        );
        $PHORUM['template_http_path'] = preg_replace(
            "/^$origq/", $PHORUM['http_path'],
            $PHORUM['template_http_path']
        );
    }
}

function mod_auto_http_path_empty_javascript()
{
    return "// Empty stub javascript for the auto_http_path module.";
}

function phorum_mod_auto_http_path_javascript_register($data)
{
    // JavaScript for this module. This module does not add actual
    // code, but we need to add this to force Phorum into using
    // different cached files per generated http_path.
    $data[] = array(
        'module'    => 'auto_http_path',
        'source'    => 'function(mod_auto_http_path_empty_javascript)',
        'cache_key' => $GLOBALS['PHORUM']['http_path']
    );

    return $data;
}

function mod_auto_http_path_empty_css()
{
    return "/* Empty stub CSS for the auto_http_path module. */";
}

function phorum_mod_auto_http_path_css_register($data)
{
    // CSS for this module. This module does not add actual
    // code, but we need to add this to force Phorum into using
    // different cached files per generated http_path.
    $data['register'][] = array(
        'module'    => 'auto_http_path',
        'source'    => 'function(mod_auto_http_path_empty_css)',
        'where'     => 'after',
        'cache_key' => $GLOBALS['PHORUM']['http_path']
    );

    return $data;
}

// Fix http paths for data that doesn't follow the http_path setting
// dynamically (e.g. the smileys module, which caches full image URLs
// using the standard http_path setting).
function phorum_mod_auto_http_path_format_fixup($messages)
{
    global $PHORUM;

    // Do not run this code from the admin interface, otherwise the
    // HTTP Path setting would act on the generated http_path.
    if (defined('PHORUM_ADMIN')) return $messages;

    $orig_http_path  = preg_replace('!/+$!', '', $PHORUM['http_path_orig']);

    foreach ($messages as $id => $message)
    {
        if (isset($message['subject'])) {
            $messages[$id]['subject'] = str_replace(
                $orig_http_path . '/',
                $PHORUM['http_path'] . '/',
                $message['subject']
            );
        }
        if (isset($message['body'])) {
            $messages[$id]['body'] = str_replace(
                $orig_http_path . '/',
                $PHORUM['http_path'] . '/',
                $message['body']
            );
        }
    }

    return $messages;
}

?>
