<?php
//
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Find
// SOFTWARE RELEASE: eZODF
// COPYRIGHT NOTICE: Copyright (C) 2007 eZ Systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

/*! \file ezrestodfhandler.php
*/

/*!
  \class eZRESTODFHandler ezrestodfhandler.php
  \brief The class eZRESTODFHandler does

*/

class eZRESTODFHandler extends eZRESTBaseHandler
{
    /**
     * @reimp
     */
    public function initialize()
    {
        $moduleDefinition = new eZRESTModuleDefinition();

        // Add views for eZRESTODFHandler
        $moduleDefinition->addView( 'getTreeStructure', array( 'method' => 'getTreeStructure',
                                                               'functions' => 'client',
                                                               'getParams' => array( 'parentNodeID', 'depth', 'limit' ) ) );
        $moduleDefinition->addView( 'getNodeInfo', array( 'method' => 'getNodeInfo',
                                                          'functions' => 'client',
                                                          'getParams' => array( 'nodeID' ),
                                                          'getOptions' => array( 'languageCode' => false ) ) );
        $moduleDefinition->addView( 'fetchOONode', array( 'method' => 'fetchOONode',
                                                          'functions' => 'client',
                                                          'getParams' => array( 'nodeID' ),
                                                          'postOptions' => array( 'languageCode' => false ) ) );
        $moduleDefinition->addView( 'putOONode', array( 'method' => 'putOONode',
                                                        'functions' => 'client',
                                                        'postParams' => array( 'nodeID', 'data' ),
                                                        'postOptions' => array( 'languageCode' => false ) ) );
        $moduleDefinition->addView( 'replaceOONode', array( 'method' => 'replaceOONode',
                                                            'functions' => 'client',
                                                            'getParams' => array( 'nodeID', 'data' ),
                                                            'postOptions' => array( 'languageCode' => null ) ) );

        // Add access functions for eZRESTODFHandler
        $moduleDefinition->addFunction( 'client', array() );
        return $moduleDefinition;
    }

    /**
     * Get eZContentObjectTreeNode information
     *
     * @param Array getParameters.
     * @param Array getOptions.
     * @param Array postParameters.
     * @param Array postOptions.
     *
     * @return DOMElement DOMElement containing node information. Return format:
     *
     * <Node nodeID="123" parentID="120">
     *     <Object ID="153" sectionID="2" mainNodeID="123" initialLanguage="eng-GB" published="123213412" modified="1251231542">
     *         <Class ID="12" primaryLanguage="eng-GB">
     *             <NameList>
     *                 <Name locale="eng-GB">eZ Publish rocks</Name>
     *             </NameList>
     *         </Class>
     *         <NameList>
     *             <Name locale="eng-GB">eZ Publish rocks</Name>
     *             <Name locale="nor-NO">eZ Publish rokker</Name>
     *         </NameList>
     *         <Owner objectID="135" primaryLanguage="nor-NO">
     *             <NameList>
     *                 <Name locale="nor-NO">Balle Klorin</Name>
     *             </NameList>
     *         </Owner>
     *     </Object>
     *     <AccessRights canRead="1" canCreate="1" canEdit="0" />
     * </Node>
     *
     */
    public function getNodeInfo( $getParams, $getOptions, $postParams, $postOptions )
    {
        $nodeID = $getParams['nodeID'];
        $languageCode = $getOptions['languageCode'];

        $domDocument = new DOMDocument( '1.0', 'utf-8' );
        $nodeElement = $domDocument->createElement( 'Node' );

        $node = eZContentObjectTreeNode::fetch( $nodeID, $languageCode );

        // Set attributes.
        $nodeElement->setAttribute( 'nodeID', $node->attribute( 'node_id' ) );
        $nodeElement->setAttribute( 'parentID', $node->attribute( 'parent_node_id' ) );

        // Get access rights element.
        $nodeElement->appendChild( $this->createAccessDOMElement( $domDocument, $node ) );

        // Get object element.
        $nodeElement->appendChild( $this->createObjectDOMElement( $domDocument, $node->attribute( 'object' ) ) );

        return $nodeElement;
    }

    /**
     * Create Object element.
     *
     * @param DOMDocument Owner DOMDocument
     * @param eZContentObject eZContentObject object.
     *
     * @return DOMElement Object DOMDocument, example:
     *
     *     <Object ID="153" mainNodeID="123" initialLanguage="eng-GB" published="123213412" modified="1251231542">
     *         <Class ID="12" primaryLanguage="eng-GB">
     *             <NameList>
     *                 <Name locale="eng-GB">eZ Publish rocks</Name>
     *             </NameList>
     *         </Class>
     *         <NameList>
     *             <Name locale="eng-GB">eZ Publish rocks</Name>
     *             <Name locale="nor-NO">eZ Publish rokker</Name>
     *         </NameList>
     *         <Owner objectID="135" primaryLanguage="nor-NO">
     *             <NameList>
     *                 <Name locale="nor-NO">Balle Klorin</Name>
     *             </NameList>
     *         </Owner>
     *         <Section ID="2" name="News" />
     *     </Object>
     */
    protected function createObjectDOMElement( DOMDocument $domDocument, eZContentObject $object )
    {
        $objectElement = $domDocument->createElement( 'Object' );

        // Set attributs.
        $objectElement->setAttribute( 'mainNodeID', $object->attribute( 'main_node_id' ) );
        $objectElement->setAttribute( 'initialLanguage', $object->attribute( 'initial_language_code' ) );
        $objectElement->setAttribute( 'published', $object->attribute( 'published' ) );
        $objectElement->setAttribute( 'modified', $object->attribute( 'modified' ) );

        // Add Class element.
        $objectElement->appendChild( $this->createClassDOMElement( $domDocument, $object->attribute( 'content_class' ) ) );

        // Add language list.
        $objectElement->appendChild( $this->createNameListDOMElementFromContentObject( $domDocument, $object ) );

        // Add owner element.
        $objectElement->appendChild( $this->createOwnerDOMElement( $domDocument, $object->attribute( 'owner' ) ) );

        // Add Section element.
        $objectElement->appendChild( $this->createSectionDOMElement( $domDocument, eZSection::fetch( $object->attribute( 'section_id' ) ) ) );

        return $objectElement;
    }

    /**
     * Create Section element.
     *
     * @param DOMDocument Owner DOMDocument
     * @param eZSection eZSection object
     *
     * @return DOMElement Section DOMElement, example:
     *
     *     <Section ID="2" name="News" />
     */
    protected function createSectionDOMElement( DOMDocument $domDocument, eZSection $section )
    {
        $sectionElement = $domDocument->createElement( 'Section' );

        // Set attributes
        $sectionElement->setAttribute( 'ID', $section->attribute( 'id' ) );
        $sectionElement->setAttribute( 'name', $section->attribute( 'name' ) );

        return $sectionElement;
    }

    /**
     * Create Owner element.
     *
     * @param DOMDocument Owner DOMDocument
     * @param eZContentObject Owner object
     *
     * @return DOMElement Owner DOMElement, example:
     *
     *     <Owner objectID="135" primaryLanguage="nor-NO">
     *         <NameList>
     *             <Name locale="nor-NO">Balle Klorin</Name>
     *         </NameList>
     *     </Owner>
     */
    protected function createOwnerDOMElement( DOMDocument $domDocument, eZContentObject $owner )
    {
        $ownerElement = $domDocument->createElement( 'Owner' );

        // Set attributes
        $ownerElement->setAttribute( 'objectID', $owner->attribute( 'id' ) );
        $ownerElement->setAttribute( 'primaryLanguage', $owner->attribute( 'initial_language_code' ) );

        // Add language list
        $ownerElement->appendChild( $this->createNameListDOMElementFromContentObject( $domDocument, $owner ) );

        return $ownerElement;
    }

    /**
     * Create Class element.
     *
     * @param DOMDocument Owner DOMDocument
     * @param eZContentClass eZContentClass object.
     *
     * @return DOMElement Class DOMDocument, example:
     *
     *     <Class ID="12" primaryLanguage="eng-GB">
     *         <NameList>
     *             <Name locale="eng-GB">eZ Publish rocks</Name>
     *         </NameList>
     *     </Class>
     */
    protected function createClassDOMElement( DOMDocument $domDocument, eZContentClass $class )
    {
        $classElement = $domDocument->createElement( 'Class' );

        // Set attributes
        $classElement->setAttribute( 'ID', $class->attribute( 'id' ) );
        $classElement->setAttribute( 'primaryLanguage', $class->attribute( 'top_priority_language_locale' ) );

        // Add Language list.
        $classElement->appendChild( $this->createNameListDOMElement( $domDocument, $class->NameList ) );

        return $classElement;
    }

    /**
     * Create NameList element.
     *
     * @param DOMDocument Owner DOMDocument
     * @param eZSerializedObjectNameList eZSerializedObjectNameList object.
     *
     * @return DOMElement NameList DOMDocument, example:
     *
     *     <NameList>
     *         <Name locale="eng-GB">eZ Publish rocks</Name>
     *     </NameList>
     */
    protected function createNameListDOMElement( DOMDocument $domDocument, eZSerializedObjectNameList $nameList )
    {
        $languageListElement = $domDocument->createElement( 'NameList' );

        // Add language names.
        foreach( $nameList->languageLocaleList() as $locale )
        {
            $languageElement = $domDocument->createElement( 'Name' );
            $languageElement->setAttribute( 'locale', $locale );
            $languageElement->appendChild( $domDocument->createTextNode( $nameList->nameByLanguageLocale( $locale ) ) );
            $languageListElement->appendChild( $languageElement );
        }

        return $languageListElement;
    }

    /**
     * Create NameList element.
     *
     * @param DOMDocument Owner DOMDocument
     * @param eZContentObject eZContentObject object.
     *
     * @return DOMElement NameList DOMDocument, example:
     *
     *     <NameList>
     *         <Name locale="eng-GB">eZ Publish rocks</Name>
     *     </NameList>
     */
    protected function createNameListDOMElementFromContentObject( DOMDocument $domDocument, eZContentObject $object )
    {
        $languageListElement = $domDocument->createElement( 'NameList' );

        // Add language names.
        foreach( $object->attribute( 'current' )->translationList( false, false ) as $locale )
        {
            $languageElement = $domDocument->createElement( 'Name' );
            $languageElement->setAttribute( 'locale', $locale );
            $languageElement->appendChild( $domDocument->createTextNode( $object->name( false, $locale ) ) );
            $languageListElement->appendChild( $languageElement );
        }

        return $languageListElement;
    }

    /**
     * Create Access element.
     *
     * @param DOMDocument Owner DOMDocument
     * @param eZContentObjectTreeNode eZContentObjectTreeNode object.
     *
     * @return DOMElement AccessRights DOMDocument, example:
     *         <AccessRights canRead="1" canCreate="1" canEdit="0" />
     */
    protected function createAccessDOMElement( DOMDocument $domDocument, eZContentObjectTreeNode $node )
    {
        $accessElement = $domDocument->createElement( 'AccessRights' );
        $accessElement->setAttribute( 'canRead', $node->attribute( 'can_read' ) ? '1' : '0' );
        $accessElement->setAttribute( 'canEdit', $node->attribute( 'can_edit' ) ? '1' : '0' );
        $accessElement->setAttribute( 'canCreate', $node->attribute( 'can_create' ) ? '1' : '0' );
        return $accessElement;
    }
}

?>