<form enctype="multipart/form-data" method="post" action={"/ezodf/import"|ezurl}>
{section show=$error} {* $error can be either bool=false or array *}
    {section show=$error.number|ne(0)}
       <div class="message-warning"><h2><span class="time">[{currentdate()|l10n( shortdatetime )}]</span>{$error.number}) {$error.message} </h2></div>
    {/section}
{/section}

{section show=eq($oo_mode,'imported')}
<div class="message-feedback"><h2><span class="time">[{currentdate()|l10n( shortdatetime )}]</span> {"Document is now imported"|i18n("extension/ezodf")}</h2></div>
{/section}
<div class="context-block">

{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">

<h1 class="context-title">{"OpenOffice.org import"|i18n("extension/ezodf")}</h1>

{* DESIGN: Mainline *}<div class="header-mainline"></div>

{* DESIGN: Header END *}</div></div></div></div></div></div>

{* DESIGN: Content START *}<div class="box-ml"><div class="box-mr"><div class="box-content">

<div class="context-attributes">

 <img align="right" src={"ooo_logo.gif"|ezimage} alt="OpenOffice.org" />

{section show=eq($oo_mode,'imported')}

<h2>{"Document is now imported"|i18n("extension/ezodf")}</h2>
<ul>
  <li>{"The object was imported as: %class_name"|i18n('extension/ezodf','', hash( '%class_name', $class_identifier ) )}</li>
  <li>{"Document imported as"|i18n("extension/ezodf")} <a href={$url_alias|ezurl}>{$node_name|wash}</a>.</li>
  <li>{"The images are placed in the media library and can be re-used."|i18n("extension/ezodf")}</li>
  <li><a href={"/ezodf/import"|ezurl}>{"Import another document"|i18n("extension/ezodf")}</a></li>
</ul>

</div>

{* DESIGN: Content END *}</div></div></div>

<div class="controlbar">
{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
<div class="block">
	<input class="button" type="submit" name="StoreButton" value="{'Upload file'|i18n('extension/ezodf')}" />
</div>

{section-else}


<h2>{"Import OpenOffice.org document"|i18n("extension/ezodf")}</h2>

{section show=$import_type|eq( "replace" )}
<h3>{"Replace document"|i18n("extension/ezodf")}: {$import_node.name|wash}</h3>
{section-else}
<h3>{"Import to"|i18n("extension/ezodf")}: {$import_node.name|wash}</h3>
{/section}


<p>
{"You can import OpenOffice.org Writer documents directly into eZ Publish from this page. You are
asked where to place the document and eZ Publish does the rest. The document is converted into
the appropriate class during the import, you get a notice about this after the import is done.
Images are placed in the media library so you can re-use them in other articles."|i18n("extension/ezodf")}
</p>

<input type="hidden" name="MAX_FILE_SIZE" value="40000000"/>
<input class="box" name="oo_file" type="file" />

</div>

{* DESIGN: Content END *}</div></div></div>

<div class="controlbar">
{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
<div class="block">
	<input class="button" type="submit" name="StoreButton" value="{'Upload file'|i18n('extension/ezodf')}" />
</div>

{/section}

{* DESIGN: Control bar END *}</div></div></div></div></div></div>
</div>

</div>

</form>
