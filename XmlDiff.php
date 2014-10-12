<?php
/**
* XmlDiff and XMLcontainer classes.
*
* Copyright (c) 2014 https://github.com/ppKrauss/PHP-XmlDiff
* Licensed under The MIT License
*
* @copyright Copyright 2014 (c) ppkrauss
* @version 0.1
* @license MIT License (http://www.opensource.org/licenses/mit-license.php)
*/

require_once 'FineDiff.php';

/**
 * XmlDiff - rendering XML differences. 
 */
class XmlDiff {

	// Configs:
	public $opt_doTree  = true; 	// generates a XPath tree
	public $opt_popLink = true; 	// show link to individual comparasion
	public $opt_rmIdx 	= false; 	// remove indexes from XPath tree
	public $opt_startDisp = true; 	// start page displaying diffs (false for hide)
	public $nodeVal_maxLen = 120; // max length for string sample showing nodeValue 	
	public $dtdGroup = array(  // stems and leaves (at each XML tree type) for layout and navigation
		''=>array( 'isHTML'=>false, 'stems'=>'', 'leaves'=>'' ) // default = no type
		,'html'=>array(
			'isHTML'=>true,  								// true when use DOMDocument::loadHTML
			'stems'=>'p|br|hr|tr|table|li|div|blockquote', 	// use to line-breaking, etc.
			'leaves'=>'span|font|b|i|u|sup|sub' 			// use to xpath-simplify, etc.
			)
		,'html5'=>array(
			'isHTML'=>true,			
			'stems'=>'p|br|hr|tr|table|li|div|blockquote|article|sec',
			'leaves'=>'span|font|b|i|u|sup|sub'
			)
		,'jats'=>array(
			'isHTML'=>false,			
			'stems'=>'p|break|hr|tr|table|list|disp-block|article|sec|front|body|back',
			'leaves'=>'bold|italic|underline|sup|sub'
			)
		,'dummy'=>array( // for test
			'isHTML'=>false,			
			'stems'=>'root|p|h1',
			'leaves'=>'i|b|sup'
			)

	);

	// Setter variables:
	public $a=NULL; // compares a with b, XMLcontainer objects
	public $b=NULL;
	public $dtd='';	// DTD-group of a and b

	// Internal use:
	private $idCount =0;
	private $idCount2=0;

	/**
	 * Constructor, trigging get() method.
	 * @param $a string empty, xml or fileName, a reference.
	 * @param $b string empty, xml or fileName, to be compared with $a.
	 * @param $dtd mix, empty, dtd-name, or an array in the form (dtd,opt_doTree,opt_popLink,opt_rmIdx).
	 */
	function __construct($a='',$b='',$dtd='') {
		if (is_array($dtd))
			list($dtd, $this->opt_doTree, $this->opt_popLink, $this->opt_rmIdx) = $dtd;
		$this->dtd = isset($this->dtdGroup[$dtd])? strtolower(trim($dtd)): '';
		$this->get($a,$b);
	}

	/**
	 * Get or refresh both XML to be compared, a and b.
	 */
	function get($a='',$b='') {
		$isHTML = $this->dtdGroup[$this->dtd]['isHTML'];
		$this->a = new XMLcontainer($a? $a: $this->$a, $isHTML);
		$this->b = new XMLcontainer($b? $b: $this->$b, $isHTML);
	}

	/**
	 * Comapare a with b.
	 */
	function compare($twoColumns=0) {
		$OUT = '';
		$startDisp = $this->opt_startDisp? 'block': 'none';
		$MAXLEN = $this->nodeVal_maxLen;
		$this->idCount++;
		$idCount = $this->idCount;
		if ($twoColumns) $MAXLEN=round($MAXLEN/2.7);
		if ($this->opt_doTree) {
			$reduce	= (isset($this->dtdGroup[$this->dtd]['leaves']))? $this->dtdGroup[$this->dtd]['leaves']: NULL;
			$a = $this->a->XMLtoc(false, "\n", $this->opt_rmIdx, $reduce);
			$b = $this->b->XMLtoc(false, "\n", $this->opt_rmIdx, $reduce);
		} else {
			$a = $this->a->dom->C14N();
			$b = $this->b->dom->C14N();
		}
		$changed = !($a==$b);
		if ($changed){
			$grain = $this->opt_doTree? FineDiff::$wordGranularity: FineDiff::$characterGranularity;
			$fineDiff = new FineDiff($a, $b, $grain);
			$diff = $fineDiff->renderDiffToHTML();
			$edits = $fineDiff->getOps();
			$changed = count($edits)-1; // non-zero
		} else
			$diff = $a;

		$idCount2 = $this->idCount2;

		if ($diff) {
			if ($this->opt_doTree) {
				$a_dom = $this->a->dom;
				$b_dom = $this->b->dom;
				$tmpRecCmd = array();
				$diff = preg_replace_callback(
					'|<([a-z]+)>(.+?)</\\1>|si',  // for tags <ins> or <del>
					function ($m) use(&$idCount2, &$tmpRecCmd, $a_dom, $b_dom, $MAXLEN) {
						$idCount2++;
						$path = $m[2];
						$cmd = $m[1];
						$txt = '';
						$dom = ($cmd=='ins')? $b_dom: $a_dom;
						$xpath = trim( join(' | ', explode("\n",$m[2]) ) , ' |');
						$xp  = new DOMXpath($dom);
						foreach(iterator_to_array( $xp->query($xpath) ) as $e) {
							$p = $e->getNodePath();
							$t = $e->nodeValue;
							if (mb_strlen($t,'UTF-8')>$MAXLEN)
								$t = mb_substr($t, 0, $MAXLEN)."...";
							$txt .= "<i>$cmd $p</i> = [{$t}]\n";
						}
						$N = count($tmpRecCmd);
						$tmpRecCmd[] = "<$cmd><a title='$cmd' href='#dfsub$idCount2' onclick='return showHide($idCount2,1)'>$m[2]</a></$cmd>"
							."<div id='dfsub$idCount2' style='display:none;padding-left:22pt;'>$txt\n</div>";
						return "__tmpRecCmd#$N#\n";
					},
					$diff
				);
				$lines=array();
				foreach(explode("\n",$diff) as $line) if ( $line=(trim($line)) )  {
					if (preg_match('/__tmpRecCmd#(\d+)#/',$line,$m))
						$lines[] = $tmpRecCmd[$m[1]];
					elseif (!$this->opt_rmIdx) {
						$a_xp   = new DOMXpath($a_dom);
						$a_node = $a_xp->query($line)->item(0);
						$b_xp   = new DOMXpath($b_dom);
						$b_node = $a_xp->query($line)->item(0);
						$a = $this->a->C14N_val($a_node);  // TO REVIEW! NO ATTRIBUTES!
						$b = $this->b->C14N_val($b_node);
						$fineDiff3 = new FineDiff($a, $b, FineDiff::$characterGranularity);
						$diff3 = trim( $fineDiff3->renderDiffToHTML() );
						$changed3 = count($fineDiff3->getOps())-1;
						if ($changed3)
							$lines[] = "<span class='changed'>$line</span>: $diff3";
						else
							$lines[] = $line;
					} else
						$lines[] = $line;
				} // for if
				$diff=join("\n",$lines);

			} // else diff is ok
			$this->idCount2 = $idCount2;
			$OUT.= "<p><b>diff$idCount</b> - Finded <b>$changed</b> changes, see:"; 
			$OUT.= " <a href='#df$idCount' onclick='return showHide($idCount)'>[open/close diff]</a> </p>";
			$OUT.= "<div id='df$idCount' style='display:$startDisp'><hr/>"
				   .($this->opt_doTree? '<pre>': '').$diff.($this->opt_doTree? '</pre>': '')
				   ."\n<p> &#160; &#160; <a href='#df$idCount' onclick='return showHide($idCount)'>[CLOSE]</a></p>\n</div>";
		} elseif ($changed) // but no $diff
			$OUT.= "<p>!!!BUG!!! changed but no diff</p>";
		else
			$OUT.= "<p>EQUAL</p>";
		$this->idCount = $idCount;
		if ($twoColumns){  // && $this->opt_doTree
			$a = $this->a->dom->C14N();
			$b = $this->b->dom->C14N();
			$fineDiff = new FineDiff($a, $b, FineDiff::$characterGranularity);
			$lastDiff = $this->normalizeSpace( $fineDiff->renderDiffToHTML(), true, false);
				$edits = $fineDiff->getOps();
				$changes = count($edits)-1; // non-zero
			$OUT2 = '<table width="100%"><tr><td width="50%" valign="top">'. $OUT .'</td>  <td width="50%" valign="top">';
			return "$OUT2<div id='xx2' style='display:$startDisp'>$lastDiff</div>  </td></tr></table>";
		} else
			return $OUT;
	} // func


   /**
    * Get and compare.
    */
	function getCompare($f, $relatof, $mode, &$idCount, &$idCount2, $twoColumns=0) {
		$this->get();
		return compare($f, $relatof, $mode, $idCount, $idCount2, $twoColumns);
	}

   /**
    * Auxiliar normalization.
    */
	function normalizeSpace($s,$spIgnore=true,$txtMode=true) {
		if ($spIgnore)
			$s = preg_replace("/\s+/s", ' ', $s);  // remove any line or spacing
		if ($this->dtdGroup[$this->dtd]['stems']) {
			$tmp = '(?:'. $this->dtdGroup[$this->dtd]['stems'] .')';
			return $txtMode
				? preg_replace('/<'.$tmp.'[\s>]/s', "\n\$0", $s)
				: preg_replace('/&lt;'.$tmp.'[\s&<]/s', "\n<br/>\$0", $s);
		} else
			return $s;
	}

} // class XmlDiff



/**
 * XML container with some facilities.
 */
class XMLcontainer {
	public $xml='';
	public $dom=NULL;
	public $isHTML=false;

	function __construct($xml='',$isHTML=false) {
		$this->xml   	= $xml;
		$this->isHTML 	= $isHTML;
		$this->get();
	}

   /**
    * Detect if string is XML (true) or filename (false).
    */
   private function isXmlString(&$s, $FILEN=500) {
	// detecta se é string de XML (retorna true) filename (false). 
	$len = strlen($s);
	if ($len>$FILEN)
		return true;
	else
		return (strpos($s,'<')===FALSE)? false: true;
   }

   /**
    * Get or refresh XML string and DOM.
    */
	function get() {
		if (!$this->isXmlString($this->xml) && $this->xml)
			$this->xml = file_get_contents($this->xml);
		if ($this->xml) {
			$this->dom = new DOMDocument('1.0','UTF-8');
			if ($this->isHTML)
				return $this->dom->loadHTML($this->xml);
			else
				return $this->dom->loadXML($this->xml);
		} else
			return ($this->dom=NULL);
	}


	/**
	 * XML's table of contents, XMLtoc (a XPath elements list)
	 * http://stackoverflow.com/q/26160675/287948
	 * @param $asarray boolean indicating to return an array
	 * @param $sep string item separator
	 * @param $reduceWhen string or array, when used is a list of non-root "stop-tags", that reduce the XPath.
	 */	  
	function XMLtoc($asarray=false, $sep="\n", $rmIdx=false, $reduceWhen=NULL) {
		$toc=array();
		$wasPath = array();
		if ($reduceWhen){
			if (is_array($reduceWhen))
				$reduceWhen = join('|',$reduceWhen);
			$reduceWhen = "#/(?:$reduceWhen)(?:[\[/].+|)\$#s"; // regex
			$c=0;		
		}
		foreach ($this->dom->getElementsByTagName('*') as $node) {
			$path = $node->getNodePath();
			if ($rmIdx)
				$path = preg_replace('/\[\d+\]/s', '', $path);
			if ($reduceWhen) {
				$path = preg_replace($reduceWhen, '', $path, -1, $c);				
				if (!$path)
					die("\nERROR in this DTD with XPaths in the (regex) form $reduceWhen\n");
				if (!$c || !isset($wasPath[$path]))
					$toc[] = $path;
				$wasPath[$path] = 1;
			} else
				$toc[] = $path;
		}
		return $asarray? $toc: join($sep,$toc);
	}

	/**
	 * Returns (NULL or) an associative (sorted) array of attributes of a node.
	 * @param $node DOMNode (not need to be a $this->dom node).
	 * @param $asString boolean, set for return list as string.
	 */	  
	function nodeAttributes($node,$asString=false){
		$ret = NULL;
		if ($node->hasAttributes()) {
			$ret = array();			
			foreach ($node->attributes as $a)
	   			$ret[$a->nodeName] = $a->nodeValue;
	   		ksort($ret);  // normalize like C14N
	   		if ($asString) {
	   			$s = '';
	   			foreach($ret as $k=>$v)
	   				$s.="$k=\"$v\" ";
	   			return $s;
	   		} else
			   	return $ret;
	   	} else
	   		return NULL;
	}

	function C14N_val($node){
		$att = $this->nodeAttributes($node,true);
		$att = $att? " $att": '';
		return "<{$node->nodeName}$att>{$node->nodeValue}</{$node->nodeName}>";
	}

} // class XMLcontainer


?>