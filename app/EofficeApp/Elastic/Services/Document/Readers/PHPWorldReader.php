<?php

namespace App\EofficeApp\Elastic\Services\Document\Readers;

use App\EofficeApp\Elastic\Services\Document\Contract\DocumentReaderInterface;
use DOMXPath;
use DomDocument;
use ZipArchive;

class PHPWorldReader implements DocumentReaderInterface
{

    const SEPARATOR_TAB = "\t";

    /**
     * object zipArchive
     *
     * @var string
     * @access private
     */
    private $docx;

    /**
     * object domDocument from document.xml
     *
     * @var string
     * @access private
     */
    private $domDocument;

    /**
     * xml from document.xml
     *
     * @var string
     * @access private
     */
    private $_document;

    /**
     * xml from numbering.xml
     *
     * @var string
     * @access private
     */
    private $_numbering;

    /**
     *  xml from footnote
     *
     * @var string
     * @access private
     */
    private $_footnote;

    /**
     *  xml from endnote
     *
     * @var string
     * @access private
     */
    private $_endnote;

    /**
     * array of all the endnotes of the document
     *
     * @var string
     * @access private
     */
    private $endnotes;

    /**
     * array of all the footnotes of the document
     *
     * @var string
     * @access private
     */
    private $footnotes;

    /**
     * array of all the relations of the document
     *
     * @var string
     * @access private
     */
    private $relations;

    /**
     * array of characters to insert like a list
     *
     * @var string
     * @access private
     */
    private $numberingList;

    /**
     * boolean variable to know if a chart will be transformed to text
     *
     * @var string
     * @access private
     */
    private $chart2text;

    /**
     * boolean variable to know if a table will be transformed to text
     *
     * @var string
     * @access private
     */
    private $table2text;

    /**
     * boolean variable to know if a list will be transformed to text
     *
     * @var string
     * @access private
     */
    private $list2text;

    /**
     * boolean variable to know if a paragraph will be transformed to text
     *
     * @var string
     * @access private
     */
    private $paragraph2text;

    /**
     * boolean variable to know if footnotes will be extracteded
     *
     * @var string
     * @access private
     */
    private $footnote2text;

    /**
     * boolean variable to know if endnotes will be extracted
     *
     * @var string
     * @access private
     */
    private $endnote2text;

    /**
     * Construct
     *
     * @param $boolTransforms array of boolean values of which elements should be transformed or not
     * @access public
     */

    public function __construct($boolTransforms = array())
    {
        //table,list, paragraph, footnote, endnote, chart
        if (isset($boolTransforms['table'])) {
            $this->table2text = $boolTransforms['table'];
        } else {
            $this->table2text = true;
        }

        if (isset($boolTransforms['list'])) {
            $this->list2text = $boolTransforms['list'];
        } else {
            $this->list2text = true;
        }

        if (isset($boolTransforms['paragraph'])) {
            $this->paragraph2text = $boolTransforms['paragraph'];
        } else {
            $this->paragraph2text = true;
        }

        if (isset($boolTransforms['footnote'])) {
            $this->footnote2text = $boolTransforms['footnote'];
        } else {
            $this->footnote2text = true;
        }

        if (isset($boolTransforms['endnote'])) {
            $this->endnote2text = $boolTransforms['endnote'];
        } else {
            $this->endnote2text = true;
        }

        if (isset($boolTransforms['chart'])) {
            $this->chart2text = $boolTransforms['chart'];
        } else {
            $this->chart2text = true;
        }

        $this->docx = null;
        $this->_numbering = '';
        $this->numberingList = array();
        $this->endnotes = array();
        $this->footnotes = array();
        $this->relations = array();

    }

    public function readContent($realPath)
    {
        try {
            if (!file_exists($realPath)) {
                return '';
            }
            ini_set('memory_limit', -1);
            $this->setDocx($realPath);
            $content = $this->extract();
        } catch (\Exception $e) {
            return '';
        }

        return $content;
    }

    /**
     *
     * Extract the content of a word document and create a text file if the name is given
     *
     * @access public
     * @param string $filename of the word document.
     *
     * @return string
     */

    private function extract($filename = '')
    {
        if (empty($this->_document)) {
            return '';
        }

        $this->domDocument = new DomDocument();
        $this->domDocument->loadXML($this->_document);
        //get the body node to check the content from all his children
        $bodyNode = $this->domDocument->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'body');
        //We get the body node. it is known that there is only one body tag
        $bodyNode = $bodyNode->item(0);
        $content = '';
        foreach ($bodyNode->childNodes as $child) {
            //the children can be a table, a paragraph or a section. We only implement the 2 first option said.
            if ($this->table2text && $child->tagName == 'w:tbl') {
                //this node is a table and  the content is split with tabs if the variable table2text from the class is true
                $content .= $this->table($child) . $this->separator();
            } else {
                //this node is a paragraph
                $content .= $this->printWP($child) . ($this->paragraph2text ? $this->separator() : '');
            }
        }
        if (!empty($filename)) {
            $this->writeFile($filename, $content);
        } else {
            return $content;
        }
    }

    /**
     * Setter
     *
     * @access public
     * @param $filename
     */
    private function setDocx($filename)
    {
        $this->docx = new ZipArchive();
        $ret = $this->docx->open($filename);
        if ($ret === true) {
            $this->_document = $this->docx->getFromName('word/document.xml');
        } else {
            $this->_document = null;
        }
    }

    /**
     * extract the content to an array from endnote.xml
     *
     * @access private
     */
    private function loadEndNote()
    {
        if (empty($this->endnotes)) {
            if (empty($this->_endnote)) {
                $this->_endnote = $this->docx->getFromName('word/endnotes.xml');
            }
            if (!empty($this->_endnote)) {
                $domDocument = new DomDocument();
                $domDocument->loadXML($this->_endnote);
                $endnotes = $domDocument->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'endnote');
                foreach ($endnotes as $endnote) {
                    $xml = $endnote->ownerDocument->saveXML($endnote);
                    $this->endnotes[$endnote->getAttribute('w:id')] = trim(strip_tags($xml));
                }
            }
        }
    }

    /**
     * Extract the content to an array from footnote.xml
     *
     * @access private
     */
    private function loadFootNote()
    {
        if (empty($this->footnotes)) {
            if (empty($this->_footnote)) {
                $this->_footnote = $this->docx->getFromName('word/footnotes.xml');
            }
            if (!empty($this->_footnote)) {
                $domDocument = new DomDocument();
                $domDocument->loadXML($this->_footnote);
                $footnotes = $domDocument->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'footnote');
                foreach ($footnotes as $footnote) {
                    $xml = $footnote->ownerDocument->saveXML($footnote);
                    $this->footnotes[$footnote->getAttribute('w:id')] = trim(strip_tags($xml));
                }
            }
        }
    }

    /**
     * Extract the styles of the list to an array
     *
     * @access private
     */
    private function listNumbering()
    {
        $ids = array();
        $nums = array();
        //get the xml code from the zip archive
        $this->_numbering = $this->docx->getFromName('word/numbering.xml');
        if (!empty($this->_numbering)) {
            //we use the domdocument to iterate the children of the numbering tag
            $domDocument = new DomDocument();
            $domDocument->loadXML($this->_numbering);
            $numberings = $domDocument->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'numbering');
            //there is only one numbering tag in the numbering.xml
            $numberings = $numberings->item(0);
            foreach ($numberings->childNodes as $child) {
                $flag = true;//boolean variable to know if the node is the first style of the list
                foreach ($child->childNodes as $son) {
                    if ($child->tagName == 'w:abstractNum' && $son->tagName == 'w:lvl') {
                        foreach ($son->childNodes as $daughter) {
                            if ($daughter->tagName == 'w:numFmt' && $flag) {
                                $nums[$child->getAttribute('w:abstractNumId')] = $daughter->getAttribute('w:val');//set the key with internal index for the listand the value it is the type of bullet
                                $flag = false;
                            }
                        }
                    } elseif ($child->tagName == 'w:num' && $son->tagName == 'w:abstractNumId') {
                        $ids[$son->getAttribute('w:val')] = $child->getAttribute('w:numId');//$ids is the index of the list
                    }
                }
            }
            //once we know what kind of list there is in the documents, is prepared the bullet that the library will use
            foreach ($ids as $ind => $id) {
                if ($nums[$ind] == 'decimal') {
                    //if the type is decimal it means that the bullet will be numbers
                    $this->numberingList[$id][0] = range(1, 10);
                    $this->numberingList[$id][1] = range(1, 10);
                    $this->numberingList[$id][2] = range(1, 10);
                    $this->numberingList[$id][3] = range(1, 10);
                } else {
                    //otherwise is *, and other characters
                    $this->numberingList[$id][0] = array('*', '*', '*', '*', '*', '*', '*', '*', '*', '*', '*', '*', '*', '*', '*', '*', '*');
                    $this->numberingList[$id][1] = array(chr(175), chr(175), chr(175), chr(175), chr(175), chr(175), chr(175), chr(175), chr(175), chr(175), chr(175), chr(175));
                    $this->numberingList[$id][2] = array(chr(237), chr(237), chr(237), chr(237), chr(237), chr(237), chr(237), chr(237), chr(237), chr(237), chr(237), chr(237));
                    $this->numberingList[$id][3] = array(chr(248), chr(248), chr(248), chr(248), chr(248), chr(248), chr(248), chr(248), chr(248), chr(248), chr(248));
                }
            }
        }
    }

    /**
     * Extract the content of a w:p tag
     *
     * @access private
     * @param $node object
     * @return string
     */
    private function printWP($node)
    {
        $ilvl = $numId = -1;
        if ($this->list2text) {//transform the list in ooxml to formatted list with tabs and bullets
            if (empty($this->numberingList)) {//check if numbering.xml is extracted from the zip archive
                $this->listNumbering();
            }
            //use the xpath to get expecific children from a node
            $xpath = new DOMXPath($this->domDocument);
            $query = 'w:pPr/w:numPr';
            $xmlLists = $xpath->query($query, $node);
            $xmlLists = $xmlLists->item(0);

            $ret = $this->toText($node);

        } else {
            //if dont want to formatted lists, we strip from html tags
            $ret = $this->toText($node);
        }


        //get the data from the charts
        if ($this->chart2text) {
            $query = 'w:r/w:drawing/wp:inline';
            $xmlChart = $xpath->query($query, $node);
            //get the relation id from the document, to get the name of the xml chart file from the relations to extract the xml code.
            foreach ($xmlChart as $chart) {
                foreach ($chart->childNodes as $child) {
                    foreach ($child->childNodes as $child2) {
                        foreach ($child2->childNodes as $child3) {
                            $rid = $child3->getAttribute('r:id');
                        }
                    }
                }
            }
        }
        //extract the expecific endnote to insert with the text content
        if ($this->endnote2text) {
            if (empty($this->endnotes)) {
                $this->loadEndNote();
            }
            $query = 'w:r/w:endnoteReference';
            $xmlEndNote = $xpath->query($query, $node);
            foreach ($xmlEndNote as $note) {
                $ret .= '[' . $this->endnotes[$note->getAttribute('w:id')] . '] ';
            }
        }
        //extract the expecific footnote to insert with the text content
        if ($this->footnote2text) {
            if (empty($this->footnotes)) {
                $this->loadFootNote();
            }
            $query = 'w:r/w:footnoteReference';
            $xmlFootNote = $xpath->query($query, $node);
            foreach ($xmlFootNote as $note) {
                $ret .= '[' . $this->footnotes[$note->getAttribute('w:id')] . '] ';
            }
        }
        if ((($ilvl != -1) && ($numId != -1)) || (1)) {
            $ret .= $this->separator();
        }

        return $ret;
    }

    /**
     * return a text end of line
     *
     * @access private
     */
    private function separator()
    {
        return "\r\n";
    }

    /**
     *
     * Extract the content of a table node from the document.xml and return a text content
     *
     * @access private
     * @param $node object
     *
     * @return string
     */
    private function table($node)
    {
        $output = '';
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                //start a new line of the table
                if ($child->tagName == 'w:tr') {
                    foreach ($child->childNodes as $cell) {
                        //start a new cell
                        if ($cell->tagName == 'w:tc') {
                            if ($cell->hasChildNodes()) {
                                //
                                foreach ($cell->childNodes as $p) {
                                    $output .= $this->printWP($p);
                                }
                                $output .= self::SEPARATOR_TAB;
                            }
                        }
                    }
                }
                $output .= $this->separator();
            }
        }
        return $output;
    }


    /**
     *
     * Extract the content of a node from the document.xml and return only the text content and. stripping the html tags
     *
     * @access private
     * @param $node object
     *
     * @return string
     */
    private function toText($node)
    {
        $xml = $node->ownerDocument->saveXML($node);
        return trim(strip_tags($xml));
    }
}