<?php

// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

$plugin['version'] = '0.1.1';
$plugin['author'] = 'Jeff Soo';
$plugin['author_uri'] = 'http://ipsedixit.net/txp/';
$plugin['description'] = 'Pagination within an article';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
# $plugin['order'] = 5;

// Plugin 'type' defines where the plugin is loaded
// 0 = public       : only on the public side of the website (default)
// 1 = public+admin : on both the public and non-AJAX admin side
// 2 = library      : only when include_plugin() or require_plugin() is called
// 3 = admin        : only on the admin side
// 4 = admin+ajax   : only on admin side
// 5 = public+admin+ajax   : on both the public and admin side
# $plugin['type'] = 0; 

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
$plugin['allow_html_help'] = 1;

// Plugin 'flags' signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use. 
defined('PLUGIN_HAS_PREFS') or define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
defined('PLUGIN_LIFECYCLE_NOTIFY') or define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

# $plugin['flags'] = PLUGIN_HAS_PREFS | PLUGIN_LIFECYCLE_NOTIFY;

if (! defined('txpinterface')) {
    global $compiler_cfg;
    @include_once('config.php');
    @include_once($compiler_cfg['path']);
}

# --- BEGIN PLUGIN CODE ---

// Register public tags.
if (class_exists('\Textpattern\Tag\Registry')) {
    Txp::get('\Textpattern\Tag\Registry')
        ->register('soo_article_page')
        ->register('soo_article_page_title')
        ->register('soo_article_page_number')
        ->register('soo_article_page_link')
        ->register('soo_article_page_nav')
        ->register('soo_if_article_page')
        ->register('soo_article_page_search_url')
        ;
}

function soo_article_page($atts)
{
    global $thisarticle, $is_article_body, $pg, $soo_article_pages;
    
    static $soo_article_page_id;

    assert_article();
    
    extract(lAtts(array(
        'delimiter' => '[break]',
        'quiet'     => 0,
    ), $atts));
    
    if ($soo_article_page_id != $thisarticle['thisid']) {
        $soo_article_page_id = $thisarticle['thisid'];
        $soo_article_pages = array();
        
        $delimiter = preg_quote($delimiter, '%');
        $delimiter = '%(<p>)?'.$delimiter.'\s*(</p>)?%';
        $pages = preg_split($delimiter, $thisarticle['body']);
        $pages = array_map('trim', $pages);
                
        $matches = array();
        foreach ($pages as $page) {
            if (preg_match('/<(h\d).*?>(.+)?<\/\1>/', $page, $matches)) {
                $header = $matches[2];
            } else {
                $header = '';
            }
            $soo_article_pages[] = array('header' => $header, 'body' => $page);
        }
        
        $soo_article_pages = array_combine(range(1, count($pages)), $soo_article_pages);
    }
    
    $page_number = $pg ? $pg : 1;
    if ($page_number > count($soo_article_pages)) {
        return '';
    }
    if ($quiet) {
        return '';
    }
    
    $was_article_body = $is_article_body;
    $is_article_body = 1;
    $out = parse($soo_article_pages[$page_number]['body']);    
    $is_article_body = $was_article_body;

    return $out;
}

function soo_article_page_title($atts)
{
    global $pg, $soo_article_pages;
    
    extract(lAtts(array(
        'page_number' => $pg ? $pg : 1,
        'class'       => '',
        'wraptag'     => '',
    ), $atts));
    
    assert_article();
    
    if (! is_int($page_number) || $page_number < 1) return '';
    $num_pages = count($soo_article_pages);
    if ($num_pages == 1 || $num_pages < $page_number) return '';

    if ($title = $soo_article_pages[$page_number]['header']) {
        return tag($title, $wraptag, array('class'=>$class));
    }
    
}

function soo_article_page_number($atts)
{
    global $pg, $soo_article_pages;
    
    assert_article();
    
    extract(lAtts(array(
        'text'    => '{page} {pg} {of} {total}',
        'class'   => '',
        'wraptag' => '',
    ), $atts));
    
    if (count($soo_article_pages) < 2) {
        return '';
    }
    
    $text = _soo_article_page_replace($text, array(
        '{total}' => count($soo_article_pages),
        '{pg}'    => $pg ? $pg : 1,
    ));    
    return tag($text, $wraptag, array('class'=>$class));
}

function soo_article_page_link($atts)
{
    global $thisarticle, $pg, $soo_article_pages;
    
    assert_article();
    
    extract(lAtts(array(
        'page_number' => 0,
        'text'        => '{next}',
        'rel'         => 'next',
        'rev'         => '',
        'showalways'  => 0,
        'title'       => '',
        'class'       => '',
        'escape'      => 'html',
    ), $atts));
    
    $thispage = $pg ? (int) $pg : 1;
    
    $total = count($soo_article_pages);
    if (($page_number > $total) || ($total == 1)) {
        return $showalways ? $text : '';
    }

    $goto = array(
        'prev'  => $thispage > 1 ? $thispage - 1 : null,
        'next'  => $thispage < $total ? $thispage + 1 : null,
        'first' => 1,
        'last'  => $total,
    );
    
    if (! array_key_exists($rel, $goto)) {
        if ($page_number > 0 && $page_number <= $total) {
            if (! $rel) {
                if (in_array($page_number, $goto)) {
                    $rel = array_search($page_number, $goto);
                } else {
                    $rel = 'page '.$page_number;
                }
            }
            $goto[$rel] = $page_number;
        } else {
            trigger_error(gTxt('invalid_attribute_value', array('{name}' => $page_number)), E_USER_NOTICE);
            return '';
        }
    }
        
    $dict = array(
        '{pg}'    => $goto[$rel],
        '{title}' => soo_article_page_title(array('page_number' => $goto[$rel])),
    );
    $text = _soo_article_page_replace($text, $dict);
        
    if ($goto[$rel] === null) {
        return $showalways ? $text : '';
    }
    
    if ($title) {
        $title = _soo_article_page_replace($title, $dict);
    }
    
    $url = permlinkurl($thisarticle);
    
    if ($url) {
        if (preg_match('/\?\w+=/', $url)) {
            $url .= '&amp;pg=';
        } else {
            $url .= '?pg=';
        }
        $url .= $goto[$rel];

        if ($escape == 'html') {
            $title = escape_title($title);
        }
        
        $revler = array('next' => 'prev', 'prev' => 'next');
        if (isset($revler[$rel])) {
            $rev = $revler[$rel];
        } elseif (! $rev) {
            if ($thispage == 1) {
                $rev = 'first';
            } elseif ($thispage == $total) {
                $rev = 'last';
            } else {
                $rev = 'page '.$thispage;
            }
        }
        
        return tag($text, 'a', array(
            'rel'   => $rel,
            'rev'   => $rev,
            'href'  => $url,
            'title' => $title,
            'class' => $class
            )
        );
    }
}

function soo_article_page_nav($atts)
{
    global $pg, $soo_article_pages;
    
    $total = count($soo_article_pages);
    if ($total < 2) {
        return '';
    }

    assert_article();
    
    extract(lAtts(array(
        'text'         => '{pg}',
        'class'        => __FUNCTION__,
        'active_class' => 'here',
        'wraptag'      => '',
        'break'        => 'br',
    ), $atts));
    
    $out = array();
    $link_atts = array(
        'class' => $class,
        'text'  => $text,
        'rel'   => '',
    );
    $active_class = $active_class ? "class=\"$active_class\"" : '';
    $page_number = $pg ? (int) $pg : 1;
    
    for ($n = 1; $n <= $total; $n++) {
        if ($n == $page_number) {
            $text = _soo_article_page_replace($text, array(
                '{title}' => soo_article_page_title(array('page_number' => $n)),
                '{pg}'    => $n,
            ));
            
            $out[] = tag($text, 'span', $active_class);
        } else {
            $link_atts['page_number'] = $n;
            $link_atts['title'] = gTxt('page').' '.$n;
            $out[] = soo_article_page_link($link_atts);
        }
    }
    
    return $out ? doWrap($out, $wraptag, $break, $class) : '';
}

function soo_if_article_page($atts, $thing)
{
    global $pg, $soo_article_pages;
    
    assert_article();
    
    if (empty($soo_article_pages)) {
        trigger_error(gTxt('tag_error').' '.__FUNCTION__.': soo_article_page '.gTxt('required'), E_USER_NOTICE);
        return parse($thing);
    }
    extract(lAtts(array(
        'first' => null,
        'last'  => null,
    ), $atts));
    
    $page = $pg ? $pg : 1;
    
    if (count($soo_article_pages) < 2) {
        $x = false;
    } elseif (! empty($last)) {
        $x = $page == count($soo_article_pages);
    } elseif (! empty($first)) {
        $x = $page == 1;
    } elseif (($first === "0") && ($last === "0")) {
        $x = ($page != 1) && ($page != count($soo_article_pages));
    } elseif ($last === "0") {
        $x = $page != count($soo_article_pages);
    } elseif ($first === "0") {
        $x = $page != 1;
    } else {
        $x = true;
    }
    
    return $thing ? parse($thing, $x) : $x; 
}

function soo_article_page_search_url($atts, $thing)
{
    extract(lAtts(array(
        'class'  => '',
        'title'  => '',
        'escape' => 'html',
    ), $atts));

    global $thisarticle, $q, $soo_article_pages;
    
    assert_article();
    
    if (empty($soo_article_pages)) {
        trigger_error(gTxt('tag_error').' '.__FUNCTION__.': soo_article_page '.gTxt('required'), E_USER_NOTICE);
        return parse($thing);
    }
    
    $found = false;
    foreach ($soo_article_pages as $n => $page) {
        if (stripos($page['body'], $q)) {
            $found = true;
            break;
        }
    }
    if (! $found) {
        if (stripos($thisarticle['title'], $q) !== false) {
            $found = $n = 1;
        }
    }
    
    if (! $found) return '';

    $url = permlinkurl($thisarticle);
    
    if (count($soo_article_pages) > 1) {
        if (preg_match('/\?\w+=/', $url)) {
            $url .= '&amp;pg=';
        } else {
            $url .= '?pg=';
        }
        $url .= $n;
    }
    
    if ($thing === null) {
        $thing = $url;
    }
    
    if ($escape == 'html') {
        $title = escape_title($title);
    }
    
    return tag(parse($thing), 'a', array(
        'href' => $url,
        'title' => $title,
        'class' => $class
    ));
}

function _soo_article_page_replace($text, $extra = array())
{
    $dict = array(
    '{page}' => gTxt('page'),
    '{of}'   => gTxt('of'),
    '{next}' => gTxt('next'),
    '{prev}' => gTxt('prev'),
    ) + $extra;
    
    return str_replace(array_keys($dict), $dict, $text);
}

# --- END PLUGIN CODE ---

?>