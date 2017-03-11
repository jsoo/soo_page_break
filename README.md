# soo_page_break

This is a plugin for [Textpattern](https://github.com/textpattern/textpattern). It provides a suite of tags 
for handling articles with body text marked up according to a couple of very simple rules: indicate page 
breaks with "[&#8203;break]" (or another delimiter of your choice), and somewhere after the break put a heading 
element (`h1`, `h2`, etc.) containing the title for that page.

See http://ipsedixit.net/txp/157/soo_page_break for further description to and  download the latest version, or grab the the compiled code from the [GitHub repo](https://github.com/jsoo/soo_page_break/releases). Either way, copy the full contents of the compiled file and paste into the 
[Textpattern plugin installer](https://docs.textpattern.io/administration/plugins-panel), and follow the instructions from there.

## Contents

* [Requirements][]
* [Usage][]
* [Tags][]
  * [soo_article_page][]
  * [soo_article_page_title][]
  * [soo_article_page_number][]
  * [soo_article_page_link][]
  * [soo_article_page_nav][]
  * [soo_if_article_page][]
  * [soo_article_page_search_url][]
* [Examples][]
* [History][]

[Requirements]: #requirements
[Usage]: #usage
[Tags]: #tags
[soo_article_page]: #soo_article_page
[soo_article_page_title]: #soo_article_page_title
[soo_article_page_number]: #soo_article_page_number
[soo_article_page_link]: #soo_article_page_link
[soo_article_page_nav]: #soo_article_page_nav
[soo_if_article_page]: #soo_if_article_page
[soo_article_page_search_url]: #soo_article_page_search_url
[Examples]: #examples
[History]: #history

<h2 id="requirements">Requirements</h2>

This plugin was developed on Textpattern 4.6 and has not been tested on earlier versions. It should run on any system capable of running Txp 4.6.2 or later. It does not install anything other than itself in the database (no extra tables, nor even prefs, although prefs might come in a later version) and is public-side only, not changing the admin side in any way. It does not require custom fields.

All of these tags require article context.

<h2 id="usage">Usage</h2>

Add page breaks to an article by inserting "[&#8203;break]" (the default delimiter) or other delimiter of your choice. For Textile-enabled articles be sure to surround the break delimiter with blank lines, otherwise the break may occur in the middle of an HTML element, producing invalid HTML output. Do not use HTML special characters in your break delimiter, unless you are certain it will never undergo Textile transformation.

For example:

<pre><code>"No," said Mr. Prendergast.

[&#8203;break]

h2. Chapter Eight</code></pre>

`soo_article_page` is a drop-in replacement for `body`. If the article has page breaks, it will output the page indicated by the URL. **soo_page_break** uses the standard Txp pagination context (i.e., the `pg` parameter in the URL's query string). Txp doesn't apply pagination to individual articles, so **soo_page_break** does not conflict with standard Txp behavior.

The rest of the tags depend on having a `soo_article_page` tag earlier in the article tag or form. You can run `soo_article_page` in quiet mode if necessary. It caches the pages it finds, so there should be no real performance hit for running it twice, even for a very long article.

`soo_article_page_title` returns the contents of the first HTML heading element (i.e., `h1`, `h2`, etc.) in a page. Rather than using the tag directly, it is generally more useful to access it through the text-replacement feature of `soo_article_page_link` or `soo_article_page_nav`, as in the following example, 

    <txp:soo_article_page_link text="{title}" />

which displays a link to the next page, using the page title as the link text.

`soo_article_page_number` displays page info, e.g. "Page 1 of 4". 
`soo_article_page_link` outputs a link to the first, previous, next, or last page, or to a particular page number. 
`soo_article_page_nav` outputs a nav widget for all pages in the article. By default it uses page numbers as link text, but can be configured to show page titles and/or other text.

`soo_if_article_page` is a conditional with various possible tests pertaining to article pages.

`soo_article_page_search_url` can be used in a search results form to link to the first article page matching the search term.

<h2 id="tags">Tags</h2>

<h3 id="soo_article_page">soo_article_page</h3>

    <txp:soo_article_page />

#### Attributes

* `delimiter` _(text)_ default `[&#8203;break]`
Text to insert into an article body to indicate a page break
* `quiet` _(boolean)_ default `0`
Whether or not to run in quiet mode, i.e., calculate page numbers without showing any output

If using a delimiter other than the default `[&#8203;break]`, and using more than one `soo_article_page` tag in an article form, set the custom `delimiter` attribute in the first of the `soo_article_page` tags.

<h3 id="soo_article_page_title">soo_article_page_title</h3>

    <txp:soo_article_page_title />

#### Attributes

* `page_number` _(int)_ default current page
Page number of the title to display
* `class` _(text)_ default empty
HTML class attribute for the link
* `wraptag` _(text)_ default empty
HTML tag name (no brackets) to wrap the output

<h3 id="soo_article_page_number">soo_article_page_number</h3>

    <txp:soo_article_page_number />

#### Attributes

* `text` _(text)_ default `{page} {pg} {of} {total}`
Output format. `{pg}` and `{total}` will be replaced by numbers. `{page}` and `{of}` will be replaced by `gTxt()` values, per Txp internationalisation settings.
* `class` _(text)_ default empty
HTML class attribute for the link
* `wraptag` _(text)_ default empty
HTML tag name (no brackets) to wrap the output

<h3 id="soo_article_page_link">soo_article_page_link</h3>

    <txp:soo_article_page_link />

#### Attributes

Required: by default this produces a link to the next page, per the `rel` attribute. If you change `rel` to something other than `first`, `last`, or `prev`, you must set `page_number` to a valid article page number to get the tag to produce a link.

* `page_number` _(int)_ default 0
Page number to link to, if not indicated by `rel`
* `text` _(text)_ default `{next}`
Link text. `{pg}` will be replaced by the page number the link points to, `{page}`, `{prev}`, and `{next}` will be replaced by `gTxt()` values, per Txp internationalisation settings, and `{title}` will be replaced by the `soo_article_page_title` of the linked page, if available.
* `rel` _(text)_ default `next`
Link relationship. If set to `first`, `last`, `prev`, or `next` the link URL will point to that page. Otherwise, the link page number will be determined by `page_number` (see above). If you set `rel=""` the HTML `rel` attribute will be determined automatically.
* `rev` _(text)_ default empty
Reversed link relationship. Leave unset to let the tag add an appropriate value.
* `showalways` _(boolean)_ default `0`
Whether or not to output `text` if the link is invalid (e.g., `rel="next"` when you are on the last page).
* `class` _(text)_ default empty
HTML class attribute for the link
* `title` _(text)_ default empty
HTML title attribute for the link. Uses the same text replacement formula as `text`, above. If you include `{title}` here and use Textile in your articles, you should also unset `escape`.
* `escape` _("html" or unset)_ default html
Escape HTML entities such as <, > and & in the `title` attribute

<h3 id="soo_article_page_nav">soo_article_page_nav</h3>

    <txp:soo_article_page_nav />

#### Attributes

* `text` _(text)_ default `{pg}` (page number)
Link text. Uses the same text replacement formula as `soo_article_page_link`'s `text` and `title` attributes.
* `class` _(text)_ default soo_article_page_nav
HTML class attribute for the wraptag
* `active_class` _(text)_ default here
HTML class attribute for the `span` tag containing the current page number
* `wraptag` _(text)_ default empty
HTML tag name (no brackets) to wrap the output
* `break` _(text)_ default br
HTML tag name (no brackets) or text to separate items

<h3 id="soo_if_article_page">soo_if_article_page</h3>

    <txp:soo_if_article_page>
       Output if true
    <txp:else />
       Output if false
    </txp:soo_if_article_page>

#### Attributes

* `first` _(boolean or unset)_ default unset
* `last` _(boolean or unset)_ default unset

#### Possible conditional tests:

<dl>
<dt>No attributes:</dt>
<dd>Does the article have multiple pages?</dd>
<dt><code>first="1"</code></dt>
<dd>Is this the first page of a multi-page article?</dd>
<dt><code>last="1"</code></dt>
<dd>Is this the last page of a multi-page article?</dd>
<dt><code>first="0"</code></dt>
<dd>Is this a page other than the first page?</dd>
<dt><code>last="0"</code></dt>
<dd>Is this a page other than the last page?</dd>
<dt><code>first="0" last="0"</code></dt>
<dd>Is this a page other than the first or last page?</dd>
</dl>

<h3 id="soo_article_page_search_url">soo_article_page_search_url</h3>

    <txp:soo_article_page_search_url /> <!-- Link text is the URL -->

    <txp:soo_article_page_search_url>Link text</txp:soo_article_page_search_url>

#### Attributes

* `class` _(text)_ default empty
HTML class attribute for the link
* `title` _(text)_ default empty
HTML title attribute for the link
* `escape` _("html" or unset)_ default html
Escape HTML entities such as <, > and & in the `title` attribute

<h2 id="examples">Examples</h2>

### In an article form

    <txp:soo_article_page quiet="1" delimiter="[page break]" />
    <header>
        <h2><txp:title /></h2>
        <txp:soo_article_page_number wraptag="p" />
    </header>
    <article>
        <txp:soo_article_page />
        <txp:soo_article_page_nav wraptag="ul" break="li" />
    </article>

Running `soo_article_page` in quiet mode is necessary here because the `soo_article_page_number` tag comes before the article body (i.e., the second `soo_article_page` tag). Note that a custom `delimiter` must be set in the first of those tags.

This form would work fine for normal (i.e., single-page) articles too, where `soo_article_page_number` and `soo_article_page_nav` will output nothing, and `soo_article_page` will output the entire article body.

### Different article forms for single-page and multi-page articles

    <txp:if_individual_article>
       <txp:soo_article_page quiet="1" />
       <txp:soo_if_article_page>
           <txp:article form="paginated" />
       <txp:else />
           <txp:article />
       </txp:soo_if_article_page>
    </txp:if_individual_article>

### Conditional text for formatting page links

    <txp:soo_article_page />
    <txp:soo_article_page_link rel="prev" text="{prev} {page}" />
    <txp:soo_if_article_page first="0" last="0"> — </txp:soo_if_article_page>
    <txp:soo_article_page_link rel="next" text="{next} {page}" />

Because the `prev` and `next` links are not set to `showalways`, we only want the separator text on a middle page, when both links are visible.

### In a search-results article form

    <txp:soo_article_page quiet="1" />
    <h3><txp:soo_article_page_search_url><txp:title /></txp:soo_article_page_search_url></h3>
    <p><txp:search_result_excerpt limit="2" />
        <br />
        <small><txp:soo_article_page_search_url /> : <txp:search_result_date /></small>
    </p>

### `soo_article_page_nav` as a numbered widget, à la Google

    <txp:soo_article_page_nav />

### `soo_article_page_nav` as a table of contents

    <txp:soo_article_page_nav wraptag="ul" break="li" text="{pg}. {title}" />

or

    <txp:soo_article_page_nav wraptag="ol" break="li" text="{title}" />

<h2 id="history">History</h2>

### 0.1.1 (2017-03-09)

* Removed `soo_page_break` tag
* Added `soo_article_page_title` tag
* New options for `soo_article_page_link` and `soo_article_page_nav`

### 0.1.0 (2017-03-08)

Initial release.
