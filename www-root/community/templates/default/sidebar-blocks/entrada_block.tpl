<section>
    <div class="panel-header">
        <h1>{$application_name}</h1>
    </div>
    <div class="panel-content">
        {if $is_logged_in}
            {$entrada_navigation}
        {else}
            <ul class="menu">
                <li><a href="{$sys_website_url}">{translate}Log In{/translate}</a></li>
            </ul>
        {/if}
    </div>
</section>
