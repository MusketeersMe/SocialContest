<?php
namespace Socon\View;

use Zend\Config\Config;
use Zend\I18n\Validator\DateTime;

class Helper {

    static $charset;

    /**
     * @param mixed $charset
     */
    public static function setCharset($charset)
    {
        self::$charset = $charset;
    }

    /**
     * @return mixed
     */
    public static function getCharset()
    {
        return self::$charset;
    }

    /**
     * escape
     *
     * Escapes/Sanitizes all data for inclusion in HTML:
     *
     * @author Eli White <eli@eliw.com>
     * @param string $value The value to be sanitized for output.
     * @param boolean $quotes Should we encode single and double quotes. Default is true, "encode"
     * @return string Escaped Value
     * @access public
     **/
    public static function escape($value, $quotes = true)
    {
        // Now handle the quoting:
        $quotes = $quotes ? ENT_QUOTES : ENT_NOQUOTES;
        return htmlspecialchars($value, $quotes | ENT_HTML5, static::$charset, FALSE);
    }

    /**
     * Converts a DateTime to the local timezone (via config) and formats it
     * @param \DateTime $datetime
     * @param string $format Default is n/j/Y ('murica)
     * @return string
     */
    public static function date(\DateTime $datetime, $format = 'n/j/Y')
    {
        $timezone = ini_get('date.timezone');
        $datetime->setTimezone(new \DateTimeZone($timezone));
        return $datetime->format($format);
    }

    /**
     * @param $username User's Twitter handle
     * @param $default_text
     * @return string
     */
    public static function congratulateTweetLink($username, $default_text)
    {
        if (0 !== strpos('@', $username)) {
            $username = '@' . $username;
        }
        $tweet = urlencode("$username $default_text");
        return 'http://twitter.com/home?status=' . $tweet;
    }

    /**
     * Format the hashtags string according to configuration
     * @param array $hashtags
     * @return string
     */
    public static function hashtags(array $hashtags)
    {
        if (1 < count($hashtags)) {
            return 'hashtags <strong>#' . implode('</strong> and <strong>#', $hashtags) . '</strong>';
        }
        return 'hashtag <strong>#' . $hashtags[0] . '</strong>';
    }

    /**
     * @param Config $contest_info
     * @return string
     */
    public static function nextWinner(Config $contest_info)
    {
        $timezone = new \DateTimeZone(ini_get('date.timezone'));
        $contest_start = new \DateTime($contest_info->start_date, $timezone);
        $contest_end = new \DateTime($contest_info->end_date, $timezone);
        list ($days, $hours, $minutes) = explode(':', $contest_info->interval);
        $interval = new \DateInterval('P' . $days . 'DT' . $hours . 'H' . $minutes . 'M');
        $now = new \DateTime('now', $timezone);
        $start_time = new \DateTime($now->format('Y-m-d') . ' ' . $contest_info->daily_start,
        $timezone);
        $end_time = new \DateTime($now->format('Y-m-d') . ' ' . $contest_info->daily_end,
            $timezone);
        $next_award = $contest_start->add($interval);

        while ($now > $next_award && $next_award <= $contest_end) {
            $next_award = $next_award->add($interval);
        }

        if ($next_award > $contest_end) {
            $next_award = $contest_end;
        }

        $next_winner = '';

        if (
            $now < $contest_end
            && $now < $end_time
            && $next_award > $start_time
            && $next_award <= $end_time
        ) {
            $next_winner = 'Next winner will be chosen at <span class="next-winner">'
                . $next_award->format('g:ia T') . '</span>.';
        }

        return $next_winner;
    }

    public static function EntryImageUrl($entry, $container_url) {

        if ($blob = $entry->getBlobName()) {
            return $container_url . '/' . $blob;
        }

        // fallback
        return $entry->getMediaUrl();
    }

}