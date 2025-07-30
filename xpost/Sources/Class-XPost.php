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
 * @version 1.0.0
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
                    $tag['content'] = XPost::parseXPost($data);
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

    public static function getTwitterEmbed(string $url): ?string
    {
        // Define the API endpoint for Twitter's oEmbed service
        $api = 'https://publish.twitter.com/oembed?url=' . urlencode($url) . '&theme=light';

        // Try to fetch the API response, retrying once if it fails
        $response = @file_get_contents($api);
        if (!$response) {
            usleep(200000); // wait 0.2 seconds before retrying
            $response = @file_get_contents($api);
        }

        if ($response) {
            $json = json_decode($response, true);
            return $json['html'] ?? null;
        }
        return null;
    }

    public static function parseXPost(string $url): string
    {
        global $txt;

        loadLanguage('XPost/');

        $embed = self::getTwitterEmbed($url);
        return $embed ?: '<div class="errorbox">'. $txt['xpost_cant_load_tweet'] .'</div>';
    }
}
