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
        add_integration_function('integrate_load_theme', __CLASS__ . '::loadTheme#', false, __FILE__);
        add_integration_function('integrate_bbc_codes', __CLASS__ . '::bbcCodes#', false, __FILE__);
        add_integration_function('integrate_bbc_buttons', __CLASS__ . '::bbcButtons#', false, __FILE__);
        add_integration_function('integrate_general_mod_settings', __CLASS__ . '::modSettings#', false, __FILE__);

    }

    public static function modSettings(&$config_vars)
    {
        global $txt;

        loadLanguage('XPost/');

        $config_vars[] = ['title', 'xpost_settings'];
        $config_vars[] = ['select', 'xpost_theme', [
            'light' => $txt['xpost_theme_light'],
            'dark' => $txt['xpost_theme_dark'],
            'auto' => $txt['xpost_theme_auto'],
        ]];
    }

    public function loadTheme(): void
    {
        global $context;

        // Only load Twitter's JS if [xpost] is used in that page.
        if (! empty($context['buffer']) && strpos($context['buffer'], '[xpost]') !== false) {
            addInlineJavaScript('
                if (!document.getElementById("twitter-wjs")) {
                    var s = document.createElement("script");
                    s.id = "twitter-wjs";
                    s.src = "https://platform.twitter.com/widgets.js";
                    document.head.appendChild(s);
                }
            ');
        }
    }

    public function bbcCodes(array &$codes): void
    {
        global $txt;

        loadLanguage('XPost/');

        $codes[] = [
            'tag' => 'xpost',
            'type' => 'unparsed_content',
            'block_level' => true,
            'validate' => function (&$tag, $data) {
                global $txt;

                if (strpos($data, '/status/') === false) {
                    $tag['content'] = '<div class="errorbox">' . $txt['xpost_link_error'] . '</div>';
                } else {
                    $tag['content'] = self::getTwitterEmbed($data) . '<span style="display:none">.</span>';
                }
            }
        ];
    }

    public function bbcButtons(array &$buttons): void
    {
        global $txt;

        loadLanguage('XPost/');

        $buttons[count($buttons) - 1][] = [
            'image'       => 'xpost',
            'code'        => 'xpost',
            'before'      => '[xpost]',
            'after'       => '[/xpost]',
            'description' => $txt['xpost_bbc'],
        ];
    }

    public static function getTwitterEmbed(string $data): ?string
    {
        global $txt, $modSettings;

        loadLanguage('XPost/');

        $maxRetries = 5; // Maximum number of retries
        $retryDelay = 200000; // Delay in microseconds (200ms)
        $timeout = 5; // Timeout in seconds
        $ttl = 86400; // Cache TTL in seconds (1 day)

        // Sanitize URL to use as cache key
        $url = trim($data);
        $cache_key = 'xpost_' . md5($url);

        // Try to get from cache first
        $cached = cache_get_data($cache_key, $ttl);
        if (!empty($cached)) {
            return $cached;
        }

        // Define the API endpoint for Twitter's oEmbed service
        $theme = $modSettings['xpost_theme'] ?? 'light';
        $apiUrl = 'https://publish.twitter.com/oembed?url=' . urlencode($url) . '&theme=' . $theme;

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
