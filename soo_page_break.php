<?php

// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 0;

$plugin['name'] = 'soo_page_break';
$plugin['version'] = '0.1.0';
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
$plugin['type'] = 0; 

// Plugin 'flags' signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use. 
defined('PLUGIN_HAS_PREFS') or define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
defined('PLUGIN_LIFECYCLE_NOTIFY') or define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

# $plugin['flags'] = PLUGIN_HAS_PREFS | PLUGIN_LIFECYCLE_NOTIFY;

defined('txpinterface') or @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---

// Register public tags.
if (class_exists('\Textpattern\Tag\Registry')) {
    Txp::get('\Textpattern\Tag\Registry')
        ->register('soo_page_break')
        ->register('soo_article_page')
        ->register('soo_article_page_number')
        ->register('soo_article_page_link')
        ->register('soo_article_page_nav')
        ->register('soo_if_article_page')
        ->register('soo_article_page_search_url')
        ;
}

function soo_page_break($atts)
{
    lAtts(array(), $atts);
    assert_article();
    return '';
}

function soo_article_page($atts)
{
    global $thisarticle, $is_article_body, $pg, $soo_article_pages, $soo_article_page_id;

    assert_article();
    
    extract(lAtts(array(
    'delimiter' => '<txp:soo_page_break />',
    'quiet'     => 0,
    ), $atts));
    
    if ($soo_article_page_id != $thisarticle['thisid']) {
        $soo_article_page_id = $thisarticle['thisid'];
        $pages = array_map('trim', explode($delimiter, $thisarticle['body']));
        $soo_article_pages = array_combine(range(1, count($pages)), $pages);
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
    $out = parse($soo_article_pages[$page_number]);    
    $is_article_body = $was_article_body;

    return $out;
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
    
    $replace = array(
        '{page}'  => gTxt('page'),
        '{of}'    => gTxt('of'),
        '{total}' => count($soo_article_pages),
        '{pg}'    => $pg ? $pg : 1,
    );
    $text = str_replace(array_keys($replace), $replace, $text);
    
    return tag($text, $wraptag, array('class'=>$class));
}

function soo_article_page_link($atts)
{
    global $thisarticle, $pg, $soo_article_pages;
    
    assert_article();
    
    extract(lAtts(array(
    'text'       => '{page} {pg}',
    'rel'        => 'next',
    'rev'        => '',
    'showalways' => 0,
    'title'      => '',
    'class'      => '',
    'escape'     => 'html',
    ), $atts));
    
    $page_number = $pg ? (int) $pg : 1;
    $total = count($soo_article_pages);
    if (($page_number > $total) || ($total == 1)) {
        return $showalways ? $text : '';
    }

    $goto = array(
        'prev'  => $page_number > 1 ? $page_number - 1 : null,
        'next'  => $page_number < $total ? $page_number + 1 : null,
        'first' => 1,
        'last'  => $total,
    );
        
    if (! array_key_exists($rel, $goto)) {
        if (is_int($text) && ($text > 0) && ($text <= $total)) {
            if (! $rel) {
                if (in_array($text, $goto)) {
                    $rel = array_search($text, $goto);
                } else {
                    $rel = 'page '.$text;
                }
            }
            $goto[$rel] = $text;
        } else {
            trigger_error(gTxt('invalid_attribute_value', array('{name}' => $rel)), E_USER_NOTICE);
            return '';
        }
    }
    
    $replace = array(
        '{page}' => gTxt('page'),
        '{next}' => gTxt('next'),
        '{prev}' => gTxt('prev'),
        '{pg}'    => $goto[$rel],
    );
    $text = str_replace(array_keys($replace), $replace, $text);
    
    if ($goto[$rel] === null) {
        return $showalways ? $text : '';
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
            if ($page_number == 1) {
                $rev = 'first';
            } elseif ($page_number == $total) {
                $rev = 'last';
            } else {
                $rev = 'page '.$page_number;
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
    'class'        => __FUNCTION__,
	'active_class' => 'here',
    'wraptag'      => '',
    'break'        => 'br',
    ), $atts));
    
    $out = array();
    $link_atts = array('class' => $class);
    $active_class = $active_class ? "class=\"$active_class\"" : '';
    $page_number = $pg ? (int) $pg : 1;
    
    for ($n = 1; $n <= $total; $n++) {
        if ($n == $page_number) {
            $out[] = tag($n, 'span', $active_class);
        } else {
            $link_atts['title'] = gTxt('page').' '.$n;
            $link_atts['text'] = $n;
            $link_atts['rel'] = '';
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
    foreach ($soo_article_pages as $n => $body) {
        if (stripos($body, $q)) {
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

# --- END PLUGIN CODE ---

if (0) {
?>
<!-- CSS SECTION
# --- BEGIN PLUGIN CSS ---
<style type="text/css">
div#sed_help pre {padding: 0.5em 1em; background: #eee; border: 1px dashed #ccc;}
div#sed_help h1, div#sed_help h2, div#sed_help h3, div#sed_help h3 code {font-family: sans-serif; font-weight: bold;}
div#sed_help h1, div#sed_help h2, div#sed_help h3 {margin-left: -1em;}
div#sed_help h2, div#sed_help h3 {margin-top: 2em;}
div#sed_help h1 {font-size: 2.4em;}
div#sed_help h2 {font-size: 1.8em;}
div#sed_help h3 {font-size: 1.4em;}
div#sed_help h4 {font-size: 1.2em;}
div#sed_help h5 {font-size: 1em;margin-left:1em;font-style:oblique;}
div#sed_help h6 {font-size: 1em;margin-left:2em;font-style:oblique;}
div#sed_help li {list-style-type: disc;}
div#sed_help li li {list-style-type: circle;}
div#sed_help li li li {list-style-type: square;}
div#sed_help li a code {font-weight: normal;}
div#sed_help li code:first-child {background: #ddd;padding:0 .3em;margin-left:-.3em;}
div#sed_help li li code:first-child {background:none;padding:0;margin-left:0;}
div#sed_help dfn {font-weight:bold;font-style:oblique;}
div#sed_help .required, div#sed_help .warning {color:red;}
div#sed_help .default {color:green;}
</style>
# --- END PLUGIN CSS ---
-->
<!-- HELP SECTION
# --- BEGIN PLUGIN HELP ---

 <div id="sed_help">

 <div id="toc">

h1. soo_page_break

h2. Contents

* "Overview":#overview
* "Usage":#usage
* "Tags":#tags
** "soo_page_break":#soo_page_break
** "soo_article_page":#soo_article_page
** "soo_article_page_number":#soo_article_page_number
** "soo_article_page_link":#soo_article_page_link
** "soo_article_page_nav":#soo_article_page_nav
** "soo_if_article_page":#soo_if_article_page
** "soo_article_page_search_url":#soo_article_page_search_url
* "Examples":#examples
* "History":#history

 </div>

h2(#overview). Overview

*soo_page_break* is a suite of tags for pagination within a single article. Add page breaks to an article body using the delimiter of your choice; show page information and navigation links; use conditionals to control output based on article page status; direct search results to the correct page of an article.

h3. Requirements

This plugin was developed on Textpattern 4.6 and has not been tested on earlier versions.

All of these tags %(required)require% article context.

h2(#usage). Usage

Add page breaks to an article by inserting a @soo_page_break@ tag or other delimiter of your choice. @soo_page_break@ is recommended as it is unambiguous, does some error checking, and outputs nothing should the article be seen by means of an ordinary @body@ tag. If Textile is enabled, make sure @p@ tags aren't being added around the delimiter, to avoid dangling @p@ tags in your page output. For example:

 <pre><code>&quot;No,&quot; said Mr. Prendergast.

notextile. notextile. &lt;txp:soo_page_break /&gt;

notextile. h2. Chapter Eight</code></pre>

@soo_article_page@ is a drop-in replacement for @body@. If the article has page breaks, it will output the page indicated by the URL. *soo_page_break* uses the standard Txp pagination context (i.e., the @pg@ parameter in the URL's query string). Txp doesn't apply pagination to individual articles, so *soo_page_break* does not conflict with standard Txp behavior.

The rest of the tags depend on having a @soo_article_page@ tag earlier in the article tag or form. You can run @soo_article_page@ in quiet mode if necessary. It caches the pages it finds, so there should be no real performance hit for running it twice, even for a very long article.

@soo_article_page_number@ displays page info, e.g. "Page 1 of 4". 
@soo_article_page_link@ outputs a link to the first, previous, next, or last page, or to a particular page number. 
@soo_article_page_nav@ outputs a nav widget with numbered links for all pages.

@soo_if_article_page@ is a conditional with various possible tests pertaining to article pages.

@soo_article_page_search_url@ can be used in a search results form to link to the first article page matching the search term.

h2(#tags). Tags

h3(#soo_page_break). soo_page_break

pre. <txp:soo_page_break />

h4. Attributes

None.

h3(#soo_article_page). soo_article_page

pre. <txp:soo_article_page />

h4. Attributes

* @delimiter@ _(text)_ %(default)default% @<txp:soo_page_break />@
Text to insert into an article body to indicate a page break
* @quiet@ _(boolean)_ %(default)default% @0@
Whether or not to run in quiet mode, i.e., calculate page numbers without showing any output

h3(#soo_article_page_number). soo_article_page_number

pre. <txp:soo_article_page_number />

h4. Attributes

* @text@ _(text)_ %(default)default% @{page} {pg} {of} {total}@
Link text. @{pg}@ and @{total}@ will be replaced by numbers. @{page}@ and @{of}@ will be replaced by @gTxt()@ values, per Txp internationalisation settings.
* @class@ _(text)_ %(default)default% empty
HTML class attribute for the link
* @wraptag@ _(text)_ %(default)default% empty
HTML tag name (no brackets) to wrap the output

h3(#soo_article_page_link). soo_article_page_link

pre. <txp:soo_article_page_link />

h4. Attributes

%(required)Required%: the tag must be given one of four standard @rel@ attributes (see below), or else the @text@ attribute must be an integer identifying a valid page number.

* @text@ _(text)_ %(default)default% @{page} {pg}@
Link text. If @text@ is an integer and a valid page number, and if @rel@ is not one of the four standard values, @text@ will also set the page number of the link URL. Otherwise, @{pg}@ will be replaced by the page number the link points to, and @{page}@, @{prev}@, and @{next}@ will be replaced by @gTxt()@ values, per Txp internationalisation settings.
* @rel@ _(text)_ %(default)default% @next@
Link relationship. If set to @first@, @last@, @prev@, or @next@ the link URL will point to that page. Otherwise, the link page number will be determined by @text@ (see above). If you set @rel=""@ and have an appropriate value for @text@, the HTML @rel@ attribute will be determined automatically.
* @rev@ _(text)_ %(default)default% empty
Reversed link relationship. Leave unset to let the tag add an appropriate value.
* @showalways@ _(boolean)_ %(default)default% @0@
Whether or not to output @text@ if the link is invalid (e.g., @rel="next"@ when you are on the last page).
* @class@ _(text)_ %(default)default% empty
HTML class attribute for the link
* @title@ _(text)_ %(default)default% empty
HTML title attribute for the link
* @escape@ _("html" or unset)_ %(default)default% html
Escape HTML entities such as <, > and & in the @title@ attribute

h3(#soo_article_page_nav). soo_article_page_nav

pre. <txp:soo_article_page_nav />

h4. Attributes

* @class@ _(text)_ %(default)default% soo_article_page_nav
HTML class attribute for the wraptag
* @active_class@ _(text)_ %(default)default% here
HTML class attribute for the @span@ tag containing the current page number
* @wraptag@ _(text)_ %(default)default% empty
HTML tag name (no brackets) to wrap the output
* @break@ _(text)_ %(default)default% br
HTML tag name (no brackets) or text to separate items

h3(#soo_if_article_page). soo_if_article_page

pre. <txp:soo_if_article_page>
    Output if true
<txp:else />
    Output if false
</txp:soo_if_article_page>

h4. Attributes

* @first@ _(boolean or unset)_ %(default)default% unset
* @last@ _(boolean or unset)_ %(default)default% unset

h4. Possible conditional tests:

; No attributes:
: Does the article have multiple pages?
; @first="1"@
: Is this the first page of a multi-page article?
; @last="1"@
: Is this the last page of a multi-page article?
; @first="0"@
: Is this a page other than the first page?
; @last="0"@
: Is this a page other than the last page?
; @first="0" last="0"@
: Is this a page other than the first or last page?

h3(#soo_article_page_search_url). soo_article_page_search_url

pre. <txp:soo_article_page_search_url /> <!-- Link text is the URL -->

pre. <txp:soo_article_page_search_url>Link text</txp:soo_article_page_search_url>

h4. Attributes

* @class@ _(text)_ %(default)default% empty
HTML class attribute for the link
* @title@ _(text)_ %(default)default% empty
HTML title attribute for the link
* @escape@ _("html" or unset)_ %(default)default% html
Escape HTML entities such as <, > and & in the @title@ attribute

h2(#examples). Examples

h3. In an article form

bc. <txp:soo_article_page quiet="1" />
<header>
    <h2><txp:title /></h2>
    <txp:soo_article_page_number wraptag="h3" />
</header>
<article>
    <txp:soo_article_page />
    <txp:soo_article_page_nav wraptag="ul" break="li" />
</article>

Running @soo_article_page@ in quiet mode is necessary here because the @soo_article_page_number@ tag comes before the article body (i.e., the second @soo_article_page@ tag). 

This form would work fine for normal (i.e., single-page) articles too, where @soo_article_page_number@ and @soo_article_page_nav@ will output nothing, and @soo_article_page@ will output the entire article body.

h3. Different article forms for single-page and multi-page articles

bc. <txp:if_individual_article>
    <txp:soo_article_page quiet="1" />
    <txp:soo_if_article_page>
        <txp:article form="paginated" />
    <txp:else />
        <txp:article />
    </txp:soo_if_article_page>
</txp:if_individual_article>

h3. Conditional text for formatting page links

bc. <txp:soo_article_page />
<txp:soo_article_page_link rel="prev" text="{prev} {page}" />
<txp:soo_if_article_page first="0" last="0"> — </txp:soo_if_article_page>
<txp:soo_article_page_link rel="next" text="{next} {page}" />

Because the @prev@ and @next@ links are not set to @showalways@, we only want the separator text on a middle page, when both links are visible.

h3. In a search-results article form

bc. <txp:soo_article_page quiet="1" />
<h3>
    <txp:soo_article_page_search_url><txp:title /></txp:soo_article_page_search_url>
</h3>
<p>
    <txp:search_result_excerpt limit="2" />
    <br />
    <small><txp:soo_article_page_search_url /> : <txp:search_result_date /></small>
</p>

h2(#history). Version History

h3. 0.1.0 (2017-03-08)

Initial release.

 </div>
 
# --- END PLUGIN HELP ---
-->
<?php
}

?>