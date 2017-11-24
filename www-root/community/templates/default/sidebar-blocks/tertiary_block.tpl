{*
 * output_selected_child - recursive function
 * Traverses the heirarchy and outputs child links of the selected menu, if they exist
 *
 * @param menu = menu item to start search
 * @param level = recursion level to prevent infinite recursion
 *}
{function name=output_selected_child level=0}
    {* If the current item has no children, nothing to do *}
    {if {$menu.link_children|@count}}
        {foreach from=$menu.link_children key=ckey2 item=child name=navigation}
            {if $menu.link_selected}
                {* The original menu item is selected, so output the link information *}
                <li><a href="{$site_community_relative}{$child.link_url}">{$child.link_title}</a><span class="divider">|</span></li>
            {else}
                {if $level < 100}
                    {* Search the children for the selected item *}
                    {output_selected_child menu=$child level=$level+1}
                {/if}
            {/if}
        {/foreach}
    {/if}
{/function}

<div class="additional-pages">
    <ul>
        {foreach from=$site_primary_navigation key=key item=menu_item name=navigation}
            {foreach from=$menu_item.link_children key=ckey1 item=child_item name=navigation}
                {output_selected_child menu=$child_item}
            {/foreach}
        {/foreach}
    </ul>
</div>