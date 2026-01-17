<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Detects browser name from user agent string.
 *
 * @param string $ua User-Agent string
 * @return string Browser name
 */
function iplogger_detect_browser($ua) {
    if (strpos($ua, 'Edg') !== false || strpos($ua, 'Edge') !== false) return 'Edge';
    if (strpos($ua, 'Chrome') !== false && strpos($ua, 'Chromium') === false) return 'Chrome';
    if (strpos($ua, 'Firefox') !== false) return 'Firefox';
    if (strpos($ua, 'Safari') !== false && strpos($ua, 'Chrome') === false) return 'Safari';
    if (strpos($ua, 'MSIE') !== false || strpos($ua, 'Trident') !== false) return 'Internet Explorer';
    return 'Unknown';
}

/**
 * Detects device type from user agent string.
 *
 * @param string $ua User-Agent string
 * @return string Device type
 */
function iplogger_detect_device($ua) {
    if (preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $ua)) return 'Mobile';
    return 'Desktop';
}

/**
 * Detects Operating System from user agent string.
 *
 * @param string $ua User-Agent string
 * @return string OS Name and Version
 */
function iplogger_detect_os($ua) {
    if (empty($ua)) return 'Unknown';

    // Regex patterns to find OS
    $os_patterns = [
        '/windows nt 10\.0/i'      => 'Windows 10/11',
        '/windows nt 6\.3/i'       => 'Windows 8.1',
        '/windows nt 6\.2/i'       => 'Windows 8',
        '/windows nt 6\.1/i'       => 'Windows 7',
        '/windows nt 6\.0/i'       => 'Windows Vista',
        '/windows nt 5\.1/i'       => 'Windows XP',
        '/android (\d+(\.\d+)?)/i' => 'Android',
        '/iphone os (\d+_\d+)/i'    => 'iOS',
        '/ipad.*os (\d+_\d+)/i'     => 'iPadOS',
        '/mac os x (\d+_\d+)/i'   => 'macOS',
        '/linux/i'                 => 'Linux',
        '/cros/i'                  => 'Chrome OS'
    ];

    foreach ($os_patterns as $pattern => $name) {
        if (preg_match($pattern, $ua, $matches)) {
            // If the pattern has a version group, append it
            if ($name === 'Android' && !empty($matches[1])) {
                return 'Android ' . $matches[1];
            }
            if (($name === 'iOS' || $name === 'iPadOS' || $name === 'macOS') && !empty($matches[1])) {
                return $name . ' ' . str_replace('_', '.', $matches[1]);
            }
            return $name;
        }
    }

    return 'Unknown'; // Default if no match
}