<!-- generator="Diogenes {$version}" -->
<rss version="2.0">
<channel>
<title>Diogenes</title>
<description>Diogenes RSS feed - {$site_title}</description>
<link>
{$site_link}
</link>
<generator>Diogenes {$version}</generator>
{foreach item=item from=$items}
<item>
  <title>{$item.title}</title>
  {if $item.link}<link>{$item.link}</link>{/if}
  {if $item.description}<description>{$item.description}</description>{/if}
  {if $item.author}<author>{$item.author}</author>{/if}
  {if $item.data}<pubDate>{$item.date}</pubDate>{/if}
</item>
{/foreach}
</channel>
</rss>
