PHP-XmlDiff
===========

An adaption of [PHP-FineDiff](https://github.com/gorhill/PHP-FineDiff) for XML comparisons.
See [motivation](http://stackoverflow.com/q/26160675/287948).

## Synopsis ##
With PHP's [DOM](http://www.w3.org/DOM) implementation, 
the [DOMDocument class](http://php.net/manual/en/class.domdocument.php), we can express the XML's tree,

```php
  	foreach ($this->dom->getElementsByTagName('*') as $node)
	     print $node->getNodePath();
``` 

and compare trees of two similar XMLs using *PHP-FineDiff*.

The project use this principle to produce tidy layout and navigation for XML comparisons.


