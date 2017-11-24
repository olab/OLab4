<section class="community-panel">
	<div class="panel-content">
		<h1>{translate}This Community{/translate}</h1>
		<p class="membership quiet">{translate}My membership{/translate}</p>
		<a href="{$sys_website_url}/profile" class="user-connected"><i class="icon-user"></i> {$member_name}</a>
		<span class="small join-date"><i class="icon-calendar"></i> {$date_joined}</span>
		<a href="{$site_community_url}:members" class="all-members"><i class="icon-th-list"></i> {translate}View all members{/translate}</a>
		<p class="quit"><a href="{$sys_website_url}/communities?section=leave&amp;community={$community_id}" class="quiet">{translate}Quit this community{/translate} <i class="icon-share-alt"></i></a></p>
	</div>
</section>