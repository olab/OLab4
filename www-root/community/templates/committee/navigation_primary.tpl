<div class="navbar">
	<div class="navbar-inner">
		<div class="span1 home-button">
			<div class="home"><a href="{$site_community_relative}{$menu_item.link_url}"{if $menu_item.link_new_window} target="_blank"{/if}><img src="{$template_relative}/images/home.png" alt="Home" /></a></div>
		</div>
		<div class="span11">
			<ul class="nav" style="width: 100%">
			{foreach from=$site_primary_navigation key=key item=menu_item name=navigation}
				{assign var="ctr" value=$ctr+1}
				{if $ctr <= 100}
					{if $smarty.foreach.navigation.first}
						
					{else}
						<li class="dropdown"><a href="{$site_community_relative}{$menu_item.link_url}"{if $menu_item.link_new_window} target="_blank"{/if}>{$menu_item.link_title}
						{if $menu_item.link_children}
							<span class="caret"></span></a>
							<ul class="dropdown-menu">
							{foreach from=$menu_item.link_children key=ckey1 item=child_item name=navigation}
								<li class="{if $child_item.link_selected} selected{/if}"><a href="{$site_community_relative}{$child_item.link_url}"{if $child_item.link_new_window} target="_blank"{/if}>{$child_item.link_title}</a></li>
							{/foreach}
							</ul>
							</li>
						{else}
							</a></li>
						{/if}
					{/if}
				{/if}
			{/foreach}
			</ul>
		</div>
	</div>
</div>