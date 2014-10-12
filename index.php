<?php
/**
 * XmlDiff DEMO 
 */
require_once 'FineDiff.php';
require_once 'XmlDiff.php';
?>
<html>
<head>
	<meta charset="utf-8">
	<title>XmlDiff comparing two XML files</title>
	<style>
		del{background:#fcc}  ins{background:#cfc} 
		.changed{background:red}
		table { border: 2px dashed green; }
		td:first-child div { background:#EEE; }
	</style>
	<script>
		function showHide(id,mode){
			var mode = (mode==undefined || !mode)? 'df': ((mode==1)? 'dfsub': 'dfpop');
			if(document.getElementById(mode+id).style.display == 'none')
				document.getElementById(mode+id).style.display = 'block';
			else
				document.getElementById(mode+id).style.display = 'none';
			return false;
		}
	</script>

</head>	

<body>
<p><b>XmlDiff</b> - see <a href="http://stackoverflow.com/q/26160675/287948" target="_blank">motivation</a> 
	and <a href="https://github.com/ppKrauss/PHP-XmlDiff" target="_blank">source</a>.</p>
<h1>Comparing A with B</h1>
<form method="post">

<?php
$xmlTree 	= @$_REQUEST['xmlTree']? 1: 0;   // not use when set
$rmleaves  	= @$_REQUEST['rmleaves']? 1: 0;
$twoColumn	= @$_REQUEST['twoColumn']? 1: 0;
$rmidx    	= @$_REQUEST['rmidx']? 1: 0;

$xml1_1 = isset($_REQUEST['xml1_1'])
	? $_REQUEST['xml1_1']
	: '<root><h1>hello</h1><p>text1</p><p>text<sup>2</sup></p><h1>bye</h1></root>';
$xml1_2 = isset($_REQUEST['xml1_2'])
	? $_REQUEST['xml1_2']
	: '<root><h2>Hello!</h2><p>text1</p><p>text2</p><h1>bye</h1></root>';
$xml1_3 = isset($_REQUEST['xml1_3'])
	? $_REQUEST['xml1_3']
	: '<root><h1>Hello!</h1><p>text222</p><p class="x">text<i>44</i><sup>2</sup></p><h1 data-x="1">bye</h1></root>';


print "<p>Using <tt>xmlTree=$xmlTree, rmidx=$rmidx</tt>, <tt>rmleaves=$rmleaves</tt>, <tt>twoColumn=$twoColumn</tt></p><hr/>";
print "<p><b>A</b> = <textarea name='xml1_1' cols=80>$xml1_1</textarea></p>";
print "<p><b>B</b> = <textarea name='xml1_2' cols=80>$xml1_2</textarea></p>";

$diff = new XmlDiff($xml1_1, $xml1_2, array('dummy',!$xmlTree,$twoColumn,$rmidx));
if (!$rmleaves) // reconfigs dtd group spec
	$diff->dtdGroup['dummy']['leaves']='';

print $diff->compare(!$twoColumn);

print "<hr/><p><b>B</b> = <textarea name='xml1_3' cols=120>$xml1_3</textarea></p>";
$diff->get($xml1_1,$xml1_3);
print $diff->compare(!$twoColumn);

?>
<hr/>

<h2>CHANGE OPTIONS</h2>

<ul type="none">
	<li> <input name="xmlTree" type="checkbox" <?php print $xmlTree?'checked=1':''; ?>/> <tt>xmlTree</tt> - NOT use "XML tree" (or handle as usual string)
		<ul type="none">
		<li> <input name="rmleaves" type="checkbox" <?php print $rmleaves?'checked=1':''; ?>/> <tt>rmleaves</tt> - remove predefined tree-leaves </li>
		<li> <input name="rmidx" type="checkbox" <?php print $rmidx?'checked=1':''; ?>/> <tt>rmidx</tt> - remove tag-index of XPathes </li>		
		</ul>
	</li>
	<li> <input name="twoColumn" type="checkbox" <?php print $twoColumn?'checked=1':''; ?>/> <tt>twoColumn</tt> - NOT show two column (tree and normal diffs).</li>
</ul>
<input type="submit" value="CHANGE"/>
</form>

</body>
</html>
