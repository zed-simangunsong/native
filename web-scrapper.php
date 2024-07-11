<?php
session_start();
include "vendor/autoload.php";

use voku\helper\HtmlDomParser;

$main = '/native/web-scrapper.php';

function storeFile($url)
{
    $filename = md5($url) . '.html';

    if (!file_exists('dummy/' . $filename) || isset($_GET['force'])) {
        $client = new GuzzleHttp\Client();
        $res = $client->get($url);
        file_put_contents('dummy/' . $filename, $res->getBody());
    }

    return 'dummy/' . $filename;
}

function itemOrDefault(array $arr, $key, $default = '')
{
    return isset($arr[$key]) ? $arr[$key] : $default;
}

function broken($arr = null, ...$keys)
{
    if (!isset($arr)) die('<h3>Broken HTML format</h3>');

    if (is_array($arr) && [] !== $keys) {
        foreach ($keys as $key)
            if (!isset($arr[$key])) broken();
    }
}

function background($content, $color = '#cccc')
{
    return '<div style="background-color:' . $color . '; padding: 0.75rem;border-radius: 10px;">' . $content . '</div>';
}

function convert($content)
{
    $content = str_replace('\u003c', '<', $content);
    $content = str_replace('\u003e', '>', $content);
    $content = str_replace('&quot;', '', $content);
    $content = str_replace('\n', '', $content);

    return $content;
}

$title = 'Hello';
$body = '';

if (isset($_GET['detail'])) {
    $key = 'permalink_question_' . $_GET['detail'];

    if (!isset($_SESSION[$key])) {
        $body = '<h1>Question not found go back to <a href="' . $main . '">home</a></h1>';
    } else {
        // Show details.
        $file = storeFile('https://www.alodokter.com/' . trim($_SESSION[$key], '/'));
        $nodes = HtmlDomParser::file_get_html($file)->find('detail-topic');

        if (count($nodes) <= 0) broken();

        foreach ($nodes as $index => $card) {
            $attr = $card->getAllAttributes();

            broken($attr, 'member-topic-title', 'member-username', 'member-topic-content');

            $body .= '<h3>' . $attr['member-topic-title'] . '</h3>';
            $body .= '<p>Asked by: ' . $attr['member-username'] . '</p>';

            $body .= background(convert($attr['member-topic-content']));
        }

        $nodes = HtmlDomParser::file_get_html($file)->find('doctor-topic');

        if (count($nodes) <= 0) broken();

        foreach ($nodes as $index => $card) {
            $attr = $card->getAllAttributes();

            broken($attr, 'by-doctor', 'doctor-topic-content');

            $body .= '<p>Answered by: ' . $attr['by-doctor'] . '</p>';

            $body .= background(convert($attr['doctor-topic-content']));
        }

        $body .= '<p>Go back to <a href="' . $main . '">home</a></p>';
    }
} elseif (isset($_GET['topic'])) {
    $key = 'permalink_topic_' . $_GET['topic'];

    if (!isset($_SESSION[$key])) {
        $body = '<h1>Topic not found go back to <a href="' . $main . '">home</a></h1>';
    } else {
        // Show questions.
        $file = storeFile('https://www.alodokter.com/komunitas/topic-tag/' . $_SESSION[$key]);
        $nodes = HtmlDomParser::file_get_html($file)->find('card-topic');

        if (count($nodes) <= 0) broken();

        $body .= '<h3>Questions</h3>';

        $body .= '<ul>';
        foreach ($nodes as $index => $card) {
            $attr = $card->getAllAttributes();

            broken($attr, 'title', 'href', 'username', 'pickup-name');

            $body .= '<li><a href="' . $main . '?detail=' . $_GET['topic'] . $index . '">' .
                (isset($attr['title']) ? $attr['title'] : 'Title not found') .
                '</a><br/>Asked by: ' . $attr['username'] .
                '<br/>Answered by: ' . $attr['pickup-name'] .
                '</li>';

            $_SESSION['permalink_question_' . $_GET['topic'] . $index] = $attr['href'];

            if ($index > 3) {
                $body .= '<li>... contact for full version.</li>';
                break;
            }
        }
        $body .= '</ul>';
    }
} else {
    // Show topic.
    $file = storeFile('https://www.alodokter.com/komunitas/topik');
    $nodes = HtmlDomParser::file_get_html($file)->find('search-a-z-topic');

    if (count($nodes) <= 0) broken();

    foreach ($nodes as $node) {
        $arr = $node->getAllAttributes();

        $body .= '<h3>' . itemOrDefault($arr, 'search-title', 'Topic') . '</h3>';
        $body .= '<ul>';

        $topics = json_decode(html_entity_decode($arr['search-results']));
        if ($topics) {
            $length = 0;
            foreach ($topics as $index => $topic) {
                if (isset($topic->permalink)) {
                    $_SESSION['permalink_topic_' . $index] = $topic->permalink;

                    $body .= '<li><a href="' . $main . '?topic=' . $index . '">'
                        . itemOrDefault((array)$topic, 'post_title', 'Title tidak ditemukan') . '</a>';

                    if ($length > 3) {
                        $body .= '<li>... contact for full version.</li>';
                        break;
                    }
                    $length++;
                }
            }

            if (0 === $length) broken();
        } else {
            broken();
        }

        $body .= '</ul>';
    }
}


echo '<html><head><title>' . $title . '</title></head><body><div style="width: 85%; margin: 10px auto;">' . $body . '</div></body></html>';
