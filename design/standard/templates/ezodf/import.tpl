<form enctype="multipart/form-data" method="post" action={"/ezodf/import"|ezurl}>
{if $error}
    {if $error.number|ne(0)}
       <div class="message-warning"><h2><span class="time">[{currentdate()|l10n( shortdatetime )}]</span>{$error.number}) {$error.message} </h2></div>
    {/if}
{/if}


{if eq($oo_mode,'imported')}
<div class="message-feedback"><h2><span class="time">[{currentdate()|l10n( shortdatetime )}]</span> {"Document is now imported"|i18n("extension/ezodf")}</h2></div>
{/if}

<div class="attribute-header">
<h1 class="long">{"OpenOffice.org import"|i18n("extension/ezodf")}</h1>
</div>

<div class="object-right">
 <img src={"ooo_logo.gif"|ezimage} alt="OpenOffice.org" />
</div>

{if eq($oo_mode,'imported')}

<h2>{"Document is now imported"|i18n("extension/ezodf")}</h2>
<ul>
  <li>{"The object was imported as: %class_name"|i18n('extension/ezodf','', hash( '%class_name', $class_identifier ) )}</li>
  {if $published}
  <li>{"Document imported as"|i18n("extension/ezodf")} <a href={$url_alias|ezurl}>{$node_name|wash}</a>.</li>
  {else}
  <li>{"The imported document is waiting for an approbation to be published."|i18n( "extension/ezodf" )}</li>
  {/if}
  <li>{"The images are placed in the media library and can be re-used."|i18n("extension/ezodf")}</li>
  <li><a href={"/ezodf/import"|ezurl}>{"Import another document"|i18n("extension/ezodf")}</a></li>
</ul>

{else}


<h2>{"Import OpenOffice.org document"|i18n("extension/ezodf")}</h2>

{if $import_type|eq( "replace" )}
<h3>{"Replace document"|i18n("extension/ezodf")}: {$import_node.name|wash}</h3>
{elseif is_set( $import_node )}
<h3>{"Import to"|i18n("extension/ezodf")}: {$import_node.name|wash}</h3>
{/if}

<p>
{"You can import OpenOffice.org Writer documents directly into eZ Publish from this page. You are
asked where to place the document and eZ Publish does the rest. The document is converted into
the appropriate class during the import, you get a notice about this after the import is done.
Images are placed in the media library so you can re-use them in other articles."|i18n("extension/ezodf")}
</p>

<fieldset>
<p>
    <label for="oo_file">{'File:'|i18n( 'design/ezodf/import' )}</label>
    <input type="hidden" name="MAX_FILE_SIZE" value="40000000"/>
    <input class="box" name="oo_file" id="oo_file" type="file" />
</p>
{def $locale_list = ezini( 'RegionalSettings', 'SiteLanguageList' )}
{if $locale_list|count|gt( 1 )}
<p>
    {if $import_type|eq( 'replace' )}
        {def $available_translations = $import_node.object.available_languages
             $options_existing = array()
             $options_new = array()}
        <label for="import-locale">{'Create or update the translation in:'|i18n( 'design/ezodf/import' )}</label>
        <select name="Locale" id="import-locale">
        {foreach ezini( 'RegionalSettings', 'SiteLanguageList' ) as $locale}
            {if $available_translations|contains( $locale )}
                {append-block variable=$options_existing}
                <option value="{$locale|wash}">{fetch( 'content', 'locale', hash( 'locale_code', $locale ) ).intl_language_name|wash()}</option>
                {/append-block}
            {else}
                {append-block variable=$options_new}
                <option value="{$locale|wash}">{fetch( 'content', 'locale', hash( 'locale_code', $locale ) ).intl_language_name|wash()}</option>
                {/append-block}
            {/if}
        {/foreach}
        {if $options_existing|count|gt( 0 )}
            <optgroup label="{'Existing translations'|i18n( 'design/ezodf/import' )}">
            {$options_existing|implode( '' )}
            </optgroup>
        {/if}
        {if $options_new|count|gt( 0 )}
            <optgroup label="{'New translations'|i18n( 'design/ezodf/import' )}">
            {$options_new|implode( '' )}
            </optgroup>
        {/if}
        </select>
        {undef $available_translations $options_existing $options_new}
    {else}
        <label for="import-locale">{'Import in:'|i18n( 'design/ezodf/import' )}</label>
        <select name="Locale" id="import-locale">
        {foreach ezini( 'RegionalSettings', 'SiteLanguageList' ) as $locale}
            <option value="{$locale|wash}">{fetch( 'content', 'locale', hash( 'locale_code', $locale ) ).intl_language_name|wash()}</option>
        {/foreach}
        </select>
    {/if}
</p>
{else}
    <input type="hidden" name="Locale" value="{$locale_list.0}" />
{/if}
{undef $locale_list}

<div class="block">
    <input class="button defaultbutton" type="submit" name="StoreButton" value="{'Import document'|i18n('extension/ezodf')}" />
</div>
</fieldset>

{/if}

</form>
