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
        $moduleDefinition->addView( 'ezodfGetTopNodeList', array( 'method' => 'ezodfGetTopNodeList',
                                                                  'functions' => 'client' ) );
        $moduleDefinition->addView( 'ezodfGetChildren', array( 'method' => 'ezodfGetChildren',
                                                              'functions' => 'client',
                                                              'getParams' => array( 'nodeID' ),
                                                              'getOptions' => array( 'languageCode' => false,
                                                                                     'offset' => 0,
                                                                                     'limit' => 10 ) ) );
        $moduleDefinition->addView( 'ezodfGetNodeInfo', array( 'method' => 'ezodfGetNodeInfo',
                                                               'functions' => 'client',
                                                               'getParams' => array( 'nodeID' ),
                                                               'getOptions' => array( 'languageCode' => false ) ) );
        $moduleDefinition->addView( 'ezodfFetchOONode', array( 'method' => 'ezodfFetchOONode',
                                                               'functions' => 'client',
                                                               'getParams' => array( 'nodeID' ),
                                                               'getOptions' => array( 'languageCode' => false ) ) );
        $moduleDefinition->addView( 'ezodfPutOONode', array( 'method' => 'putOONode',
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
     * Fetch Node for editing in OpenOffice.org
     *
     * @param Array getParameters.
     * @param Array getOptions.
     * @param Array postParameters.
     * @param Array postOptions.
     *
     * @return DOMElement DOMElement containing OO document.
     */
    public function ezodfFetchOONode( $getParams, $getOptions, $postParams, $postOptions )
    {
        $nodeID = $getParams['nodeID'];
        $languageCode = $getOptions['languageCode'];

        $node = eZContentObjectTreeNode::fetch( $nodeID, $languageCode );

        if ( !$node )
        {
            throw new Exception( 'Could not fetch node: ' . $nodeID );
        }

        $domDocument = new DOMDocument( '1.0', 'utf-8' );
        $ooElement = $domDocument->createElement( 'OONode' );

        // Add node information.
        $ooElement->appendChild( $this->createTreeNodeDOMElement( $domDocument, $node ) );

        // Add binary data element.
        $ooElement->appendChild( $this->createOODOMElement( $domDocument, $node ) );

        return $ooElement;
    }

    /**
     * Get top node list.
     *
     * @param Array getParameters.
     * @param Array getOptions.
     * @param Array postParameters.
     * @param Array postOptions.
     *
     * @return DOMElement DOMElement contating top node information.
     */
    public function ezodfGetChildren( $getParams, $getOptions, $postParams, $postOptions )
    {
        $nodeID = $getParams['nodeID'];
        $languageCode = $getOptions['languageCode'];
        $offset = $getOptions['offset'];
        $limit = $getOptions['limit'];

        $domDocument = new DOMDocument( '1.0', 'utf-8' );
        $childrenElement = $domDocument->createElement( 'Children' );

        // Set attributes.
        $childrenElement->setAttribute( 'offset', $offset );
        if ( $languageCode )
        {
            $childrenElement->setAttribute( 'languageCode', $languageCode );
        }
        $childrenElement->setAttribute( 'limit', $limit );

        // Get children, and add them to children list.
        $children = eZContentObjectTreeNode::subTreeByNodeID( array( 'Depth' => 1,
                                                                     'DepthOperator' => 'eq',
                                                                     'Limit' => $limit,
                                                                     'Offset' => $offset,
                                                                     'Language' => $languageCode ),
                                                              $nodeID );
        if ( $children )
        {
            foreach( $children as $childNode )
            {
                $childrenElement->appendChild( $this->createTreeNodeDOMElement( $domDocument, $childNode ) );
            }
            $childrenElement->setAttribute( 'count', count( $children ) );
        }
        else
        {
            $childrenElement->setAttribute( 'count', count( $children ) );
        }

        return $childrenElement;
    }

    /**
     * Get top node list.
     *
     * @param Array getParameters.
     * @param Array getOptions.
     * @param Array postParameters.
     * @param Array postOptions.
     *
     * @return DOMElement DOMElement contating top node information.
     */
    public function ezodfGetTopNodeList( $getParams, $getOptions, $postParams, $postOptions )
    {
        $domDocument = new DOMDocument( '1.0', 'utf-8' );
        $nodeListElement = $domDocument->createElement( 'TopNodeList' );

        $contentINI = eZINI::instance( 'content.ini' );
        $odfINI = eZINI::instance( 'odf.ini' );

        $elementCount = 0;

        foreach( $odfINI->variable( 'OOMenuSettings', 'TopNodeNameList' ) as $topNodeName )
        {
            $nodeID = $contentINI->variable( 'NodeSettings', $topNodeName );
            $node = eZContentObjectTreeNode::fetch( $nodeID );
            if ( !$node )
            {
                throw new Exception( 'Could not fetch node: "' . $topNodeName . '", ID: ', $nodeID );
            }
            $nodeListElement->appendChild( $this->createTreeNodeDOMElement( $domDocument, $node ) );
            $elementCount++;
        }

        $nodeListElement->setAttribute( 'count', $elementCount );

        return $nodeListElement;
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
    public function ezodfGetNodeInfo( $getParams, $getOptions, $postParams, $postOptions )
    {
        $nodeID = $getParams['nodeID'];
        $languageCode = $getOptions['languageCode'];

        $domDocument = new DOMDocument( '1.0', 'utf-8' );

        $node = eZContentObjectTreeNode::fetch( $nodeID, $languageCode );

        if ( !$node )
        {
            throw new Exception( 'Could not fetch node: ' . $nodeID );
        }

        return $this->createTreeNodeDOMElement( $domDocument, $node );
    }

    /**
     * Create Node element
     *
     * @param DOMDocument Owner DOMDocument
     * @param eZContentObject eZContentObject node.
     *
     * @return DOMElement Node DOMDocument.
     */
    protected function createTreeNodeDOMElement( DOMDocument $domDocument, eZContentObjectTreeNode $node )
    {
        $nodeElement = $domDocument->createElement( 'Node' );

        // Set attributes.
        $nodeElement->setAttribute( 'nodeID', $node->attribute( 'node_id' ) );
        $nodeElement->setAttribute( 'parentID', $node->attribute( 'parent_node_id' ) );
        $nodeElement->setAttribute( 'hasChildren', $node->childrenCount() ? '1' : '0' );

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
     *     <Class ID="12" identifier="comment" primaryLanguage="eng-GB">
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
        $classElement->setAttribute( 'identifier', $class->attribute( 'identifier' ) );

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
     * Create OO document element.
     *
     * @param DOMDocument Owner DOMDocument
     * @param eZContentObjectTreeNode eZContentObjectTreeNode object.
     *
     * @return DOMElement NameList DOMDocument, example:
     *
     *     <OODocument base64Encoded="1" filename="My article.odt">
     *         <![CDATA[ ad lkøjsdaølfhadsø fiuancfivn søgsbdnvsahfø ]]>
     *     </OODocument>
     */
    protected function createOODOMElement( DOMDocument $domDocument, eZContentObjectTreeNode $node )
    {
        $ooDocumentElement = $domDocument->createElement( 'OODocument' );

        $fileName = eZOOConverter::objectToOO( $node->attribute( 'node_id' ) );

        if ( is_array( $fileName ) )
        {
            throw new Exception( 'Could not generate OO document, ID: ' . $node->attribute( 'node_id' ) . ', Description: ' . $fileName[0] );
        }

        // Add odt document to DOMElement
        $ooDocumentElement->setAttribute( 'base64Encoded', '1' );
        $ooDocumentElement->setAttribute( 'filename', $node->attribute( 'name' ) . '.odt' );
        $ooDocumentElement->appendChild( $domDocument->createCDATASection( base64_encode( eZFile::getContents( $fileName ) ) ) );

        unlink( $fileName );

        return $ooDocumentElement;
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