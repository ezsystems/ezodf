<form enctype="multipart/form-data" method="post" action={"/oo/export"|ezurl}>

<div class="context-block">
{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
<h1 class="context-title">{"OpenOffice.org import"|i18n("design/standard/oo")}</h1>

{* DESIGN: Mainline *}<div class="header-mainline"></div>

{* DESIGN: Header END *}</div></div></div></div></div></div>

{* DESIGN: Content START *}<div class="box-ml"><div class="box-mr"><div class="box-content">

<div class="context-attributes">

   <img align="right" src={"ooo_logo.gif"|ezimage} alt="OpenOffice.org" />

<h1>Export eZ publish content to OpenOffice.org</h1>

{section show=$error_string}
   <h2>{"Error"|i18n("design/standard/oo")}: {$error_string}</h2>
{/section}

<p>
Here you can export any eZ publish content object to an OpenOffice.org Writer document format.
</p>

</div>

{* Buttons. *}
<div class="controlbar">
{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
<div class="block">

<input class="button" type="submit" name="ExportButton" value="{'Export Object'|i18n('design/standard/oo/import)}" />

</div>
{* DESIGN: Control bar END *}</div></div></div></div></div></div>
</div>

</form>