
<form enctype="multipart/form-data" method="post" action={"/oo/import"|ezurl}>

<div class="context-block">
{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
<h1 class="context-title">{"OpenOffice.org import"|i18n("design/standard/section")}</h1>

{* DESIGN: Mainline *}<div class="header-mainline"></div>

{* DESIGN: Header END *}</div></div></div></div></div></div>

{* DESIGN: Content START *}<div class="box-ml"><div class="box-mr"><div class="box-content">

<div class="context-attributes">

   <img align="right" src={"ooo_logo.gif"|ezimage} alt="OpenOffice.org" />

{section show=eq($oo_mode,'imported')}

<h1>Object is now imported<//h1>
<p>
The object was imported as: {$class_identifier}
</p>
<p>
 You can find the object <a href={$url_alias|ezurl}>here</a>.
</p>
<p>
The images are placed in the media and can be re-used.
</p>

<p>
Click <a href={"/oo/import"|ezurl}>here</a>  to import another document.
</p>

</div>

{* Buttons. *}
<div class="controlbar">
{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
<div class="block">


{section-else}

<h1>Import OpenOffice.org document</h1>

<p>
You can import OpenOffice.org Writer documents directly into eZ publish from this page. You are
aksed where to place the document and eZ publish does the rest. The document is converted into
the appropriate class during the import, you get a notice about this after the import is done.
Images are placed in the media library so you can re-use them in other articles.
</p>

<input type="hidden" name="MAX_FILE_SIZE" value="40000000"/>
<input class="box" name="oo_file" type="file" />

</div>

{* Buttons. *}
<div class="controlbar">
{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
<div class="block">

<input class="button" type="submit" name="StoreButton" value="{'Upload file'|i18n('design/standard/oo/import)}" />


{/section}

</div>
{* DESIGN: Control bar END *}</div></div></div></div></div></div>
</div>

</form>