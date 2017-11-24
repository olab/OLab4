<section class="community-panel">
	<div class="panel-header">
		<h1>{translate}This Community{/translate}</h1>
	</div>
	<div class="panel-content">
		<ul>
			<li><p><strong>{translate}My Membership{/translate}</strong></p></li>
			<li><a href="{$sys_website_url}/profile" class="user-connected"><i class="icon-user"></i> {$member_name}</a></li>
			<li><p><i class="icon-calendar"></i> {$date_joined}</p></li>
			<li><a href="{$site_community_url}:members" class="all-members"><i class="icon-th-list"></i> {translate}View all members{/translate}</a></li>
			<li><a href="{$sys_website_url}/communities?section=leave&amp;community={$community_id}" class="quiet"><i class="icon-share-alt"></i> {translate}Quit this community{/translate}</a></li>
		</ul>
	</div>
</section>