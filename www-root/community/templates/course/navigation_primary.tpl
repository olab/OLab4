<div id="sidebar" class="sidebar panel no-printing">
	<div class="panel-head"><h3>{translate}Course Navigation{/translate}</h3></div>

	<ul class="navigation">
	{foreach from=$site_primary_navigation key=key item=menu_item name=navigation}
		<li{if $menu_item.link_selected} class="selected"{/if}><a href="{$site_community_relative}{$menu_item.link_url}"{if $menu_item.link_new_window} target="_blank"{/if}>{$menu_item.link_title}</a></li>
		{foreach from=$menu_item.link_children key=ckey1 item=child_item name=navigation}
			<li class="sub-pages{if $child_item.link_selected} selected{/if}"><a href="{$site_community_relative}{$child_item.link_url}"{if $child_item.link_new_window} target="_blank"{/if}>{$child_item.link_title}</a></li>
		{/foreach}
	{/foreach}
	</ul>
</div>