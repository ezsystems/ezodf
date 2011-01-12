<script type="text/javascript">
menuArray['OpenOffice'] = [];
menuArray['OpenOffice']['depth'] = 1;
menuArray['OpenOffice']['elements'] = [];
</script>

<hr />
<a id="menu-openoffice" class="more" href="#" onmouseover="ezpopmenu_showSubLevel( event, 'OpenOffice', 'menu-openoffice' ); return false;">{'OpenOffice.org'|i18n( 'extension/ezodf/popupmenu' )}</a>

{* Export to OOo / OASIS document *}
<form id="menu-form-export-ooo" method="post" action={"/ezodf/export/"|ezurl}>
  <input type="hidden" name="NodeID" value="%nodeID%" />
  <input type="hidden" name="ObjectID" value="%objectID%" />
  <input type="hidden" name="CurrentURL" value="%currentURL%" />
</form>

{* Export to PDF *}
<form id="menu-form-export-pdf" method="post" action={"/ezodf/export/"|ezurl}>
  <input type="hidden" name="ExportType" value="PDF" />
  <input type="hidden" name="NodeID" value="%nodeID%" />
  <input type="hidden" name="ObjectID" value="%objectID%" />
  <input type="hidden" name="CurrentURL" value="%currentURL%" />
</form>

{* Export to Word *}
<form id="menu-form-export-word" method="post" action={"/ezodf/export/"|ezurl}>
  <input type="hidden" name="ExportType" value="Word" />
  <input type="hidden" name="NodeID" value="%nodeID%" />
  <input type="hidden" name="ObjectID" value="%objectID%" />
  <input type="hidden" name="CurrentURL" value="%currentURL%" />
</form>

{* Import OOo / OASIS document *}
<form id="menu-form-import-ooo" method="post" action={"/ezodf/import/"|ezurl}>
  <input type="hidden" name="NodeID" value="%nodeID%" />
  <input type="hidden" name="ObjectID" value="%objectID%" />
  <input type="hidden" name="CurrentURL" value="%currentURL%" />
</form>


{* Replace OOo / OASIS document *}
<form id="menu-form-replace-ooo" method="post" action={"/ezodf/import/"|ezurl}>
  <input type="hidden" name="ImportType" value="replace" />
  <input type="hidden" name="NodeID" value="%nodeID%" />
  <input type="hidden" name="ObjectID" value="%objectID%" />
  <input type="hidden" name="CurrentURL" value="%currentURL%" />
</form>
