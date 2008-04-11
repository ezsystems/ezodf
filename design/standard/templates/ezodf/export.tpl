<form enctype="multipart/form-data" method="post" action={"/ezodf/export"|ezurl}>

<div class="context-block">

{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">

<h1 class="context-title">{"OpenOffice.org export"|i18n("extension/ezodf")}</h1>

{* DESIGN: Mainline *}<div class="header-mainline"></div>

{* DESIGN: Header END *}</div></div></div></div></div></div>

{* DESIGN: Content START *}<div class="box-ml"><div class="box-mr"><div class="box-content">

<div class="context-attributes">

<img align="right" src={"ooo_logo.gif"|ezimage} alt="OpenOffice.org" />

<h2>{"Export eZ Publish content to OpenOffice.org"|i18n("extension/ezodf")}</h2>

{section show=$error_string}
   <h3>{"Error"|i18n("extension/ezodf")}: {$error_string}</h3>
{/section}

<p>
{"Here you can export any eZ Publish content object to an OpenOffice.org Writer document format."|i18n("extension/ezodf")}
</p>

</div>

{* DESIGN: Content END *}</div></div></div>

<div class="controlbar">
{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
<div class="block">
	<input class="button" type="submit" name="ExportButton" value="{'Export Object'|i18n('extension/ezodf')}" />
</div>

{* DESIGN: Control bar END *}</div></div></div></div></div></div>
</div>

</div>

</form>
