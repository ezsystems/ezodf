 <hr/>
    <a id="menu-export-ooo" href="#" onmouseover="ezpopmenu_mouseOver( 'ContextMenu' )"
       onclick="ezpopmenu_submitForm( 'menu-form-export-ooo' ); return false;">{"Export OpenOffice.org"|i18n("extension/ezodf/popupmenu")}</a>
    <a id="menu-export-ooo" href="#" onmouseover="ezpopmenu_mouseOver( 'ContextMenu' )"
       onclick="ezpopmenu_submitForm( 'menu-form-export-pdf' ); return false;">{"Export PDF"|i18n("extension/ezodf/popupmenu")}</a>
    <a id="menu-export-ooo" href="#" onmouseover="ezpopmenu_mouseOver( 'ContextMenu' )"
       onclick="ezpopmenu_submitForm( 'menu-form-export-word' ); return false;">{"Export Word"|i18n("extension/ezodf/popupmenu")}</a>
    <a id="menu-import-ooo" href="#" onmouseover="ezpopmenu_mouseOver( 'ContextMenu' )"
       onclick="ezpopmenu_submitForm( 'menu-form-import-ooo' ); return false;">{"Import OpenOffice.org"|i18n("extension/ezodf/popupmenu")}</a>
    <a id="menu-import-ooo" href="#" onmouseover="ezpopmenu_mouseOver( 'ContextMenu' )"
       onclick="ezpopmenu_submitForm( 'menu-form-replace-ooo' ); return false;">{"Replace OpenOffice.org"|i18n("extension/ezodf/popupmenu")}</a>

{* Export to OOo / OASIS document *}
<form id="menu-form-export-ooo" method="post" action={"/odf/export/"|ezurl}>
  <input type="hidden" name="NodeID" value="%nodeID%" />
  <input type="hidden" name="ObjectID" value="%objectID%" />
  <input type="hidden" name="CurrentURL" value="%currentURL%" />
</form>

{* Export to PDF *}
<form id="menu-form-export-pdf" method="post" action={"/odf/export/"|ezurl}>
  <input type="hidden" name="ExportType" value="PDF" />
  <input type="hidden" name="NodeID" value="%nodeID%" />
  <input type="hidden" name="ObjectID" value="%objectID%" />
  <input type="hidden" name="CurrentURL" value="%currentURL%" />
</form>

{* Export to Word *}
<form id="menu-form-export-word" method="post" action={"/odf/export/"|ezurl}>
  <input type="hidden" name="ExportType" value="Word" />
  <input type="hidden" name="NodeID" value="%nodeID%" />
  <input type="hidden" name="ObjectID" value="%objectID%" />
  <input type="hidden" name="CurrentURL" value="%currentURL%" />
</form>

{* Import OOo / OASIS document *}
<form id="menu-form-import-ooo" method="post" action={"/odf/import/"|ezurl}>
  <input type="hidden" name="NodeID" value="%nodeID%" />
  <input type="hidden" name="ObjectID" value="%objectID%" />
  <input type="hidden" name="CurrentURL" value="%currentURL%" />
</form>


{* Replace OOo / OASIS document *}
<form id="menu-form-replace-ooo" method="post" action={"/odf/import/"|ezurl}>
  <input type="hidden" name="ImportType" value="replace" />
  <input type="hidden" name="NodeID" value="%nodeID%" />
  <input type="hidden" name="ObjectID" value="%objectID%" />
  <input type="hidden" name="CurrentURL" value="%currentURL%" />
</form>
