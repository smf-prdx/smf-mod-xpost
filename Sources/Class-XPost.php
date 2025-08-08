<?php

/**
 * Class-XPost.php
 *
 * @package prdx:xpost
 * @link https://cientoseis.es
 * @author prdx (https://www.simplemachines.org/community/index.php?action=profile;u=674744)
 * @copyright 2025, prdx
 * @license https://opensource.org/licenses/BSD-3-Clause BSD
 *
 */

if (!defined('SMF'))
    die('No direct access...');

final class XPost
{
    public function hooks(): void
    {
        add_integration_function('integrate_pre_javascript_output', __CLASS__ . '::injectScript', false, __FILE__);
        add_integration_function('integrate_bbc_codes', __CLASS__ . '::bbcCodes#', false, __FILE__);
        add_integration_function('integrate_bbc_buttons', __CLASS__ . '::bbcButtons#', false, __FILE__);
        add_integration_function('integrate_general_mod_settings', __CLASS__ . '::modSettings#', false, __FILE__);

    }

    private static function localLoadLanguage(string $template = 'XPost/'): void
    {
        $is_package_area = isset($_GET['action'], $_GET['area']) &&
            $_GET['action'] === 'admin' &&
            $_GET['area'] === 'packages';

        // if we're in package area (installing or uninstalling), do not consider a fatal error if it can't be loaded
        // because the language file may not exist in that case.
        loadLanguage($template, '', !$is_package_area);
    }

    public static function modSettings(&$config_vars)
    {
        global $txt;

        self::localLoadLanguage();

        $config_vars[] = ['title', 'xpost_settings'];
        $config_vars[] = ['select', 'xpost_theme', [
            'light' => $txt['xpost_theme_light'],
            'dark' => $txt['xpost_theme_dark'],
            'auto' => $txt['xpost_theme_auto'],
        ]];
    }

    // Inject the Twitter script into the page if the mod is enabled.
    // This will load the Twitter widgets.js script asynchronously just once per page.
    public static function injectScript($do_deferred): void
    {
        addInlineJavaScript('
            window.twttr = (function(d, s, id) {
                var js, fjs = d.getElementsByTagName(s)[0],
                    t = window.twttr || {};
                if (d.getElementById(id)) return t;
                js = d.createElement(s);
                js.id = id;
                js.src = "https://platform.twitter.com/widgets.js";
                fjs.parentNode.insertBefore(js, fjs);

                t._e = [];
                t.ready = function(f) {
                    t._e.push(f);
                };

                return t;
            }(document, "script", "twitter-wjs"));
        ');
    }

    public function bbcCodes(array &$codes): void
    {
        global $txt;

        self::localLoadLanguage();

        $codes[] = [
            'tag' => 'xpost',
            'type' => 'unparsed_content',
            'block_level' => true,
            'validate' => function (&$tag, $data) {
                global $txt;

                // Validate the input data to ensure it is a valid Twitter URL (posts, timelines, profiles, likes, moments, etc.)
                $url = preg_replace('/\?.*/', '', trim($data));
                if (!preg_match('~^https://(x\.com|twitter\.com)/~i', $url)) {
                    $tag['content'] = '<div class="errorbox">' . $txt['xpost_link_error'] . '</div>';
                } else {
                    $tag['content'] = self::getTwitterEmbed($url) . '<span style="display:none">.</span>';
                }
            }
        ];
    }

    public function bbcButtons(array &$buttons): void
    {
        global $txt;

        self::localLoadLanguage();

        $buttons[count($buttons) - 1][] = [
            'image'       => 'xpost',
            'code'        => 'xpost',
            'before'      => '[xpost]',
            'after'       => '[/xpost]',
            'description' => $txt['xpost_bbc'],
        ];
    }

    public static function getTwitterEmbed(string $url): ?string
    {
        global $txt, $modSettings;

        self::localLoadLanguage();

        $maxRetries = 5; // Maximum number of retries
        $retryDelay = 200000; // Delay in microseconds (200ms)
        $timeout = 5; // Timeout in seconds
        $ttl = 86400; // Cache TTL in seconds (1 day)

        $cache_key = 'xpost_' . md5($url);

        // Try to get from cache first
        $cached = cache_get_data($cache_key, $ttl);
        if (!empty($cached)) {
            return $cached;
        }

        // Define the API endpoint for Twitter's oEmbed service
        $theme = $modSettings['xpost_theme'] ?? 'light';
        $apiUrl = 'https://publish.twitter.com/oembed?url=' . urlencode($url) . '&theme=' . $theme . '&omit_script=1';

        // Try to fetch the API response, retrying once if it fails
        $response = false;
        for ($i = 0; $i < $maxRetries; $i++) {
            $response = @file_get_contents($apiUrl, false, stream_context_create([
                'http' => [
                    'timeout' => $timeout,
                    'header' => "Accept: application/json\r\n"
                ]
            ]));
            if ($response !== false) {
                break;
            }
            usleep($retryDelay);
        }

        if ($response) {
            $json = json_decode($response, true);

            // Basic error handling
            if (!empty($json['errors'])) {
                foreach ($json['errors'] as $error) {
                    if (strpos($error['message'], 'not authorized') !== false || strpos($error['message'], 'private') !== false) {
                        return '<div class="errorbox">' . $txt['xpost_not_authorized'] . '</div>';
                    }
                }
                return '<div class="errorbox">'. $txt['xpost_cant_load_tweet'] .'</div>';;
            }

            if (!empty($json['html'])) {
                cache_put_data($cache_key, $json['html'], 86400);
                return $json['html'];
            }
        }
        return '<div class="errorbox">'. $txt['xpost_cant_load_tweet'] .'</div>';;
    }
}
