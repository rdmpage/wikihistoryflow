# Wiki History Flow

#### Overview
This code creates a simple web site that queries Wikipedia to get the edit history of a page and renders it as a "history flow" in SVG. For background see [Visualising edit history of a Wikipedia page](http://iphylo.blogspot.com/2009/09/visualising-edit-history-of-wikipedia.html). Inspiration came from Jeff Attwood's post [Mixing Oil and Water: Authorship in a Wiki World](http://www.codinghorror.com/blog/archives/001222.html), which discusses the [History Flow project](http://researchweb.watson.ibm.com/visual/projects/history_flow/explanation.htm). 

Below is the history flow for the [Wikipedia article on Phylogeny](http://en.wikipedia.org/wiki/Phylogeny):

![History flow for Phylogeny](https://github.com/rdmpage/wikihistoryflow/raw/master/historyflow.png)

After grabbing the XML for a Wikipedia page the script breaks the text into lines and uses [Text_Diff](http://pear.php.net/package/Text_Diff) to compute the difference between these lines. It then creates a simple SVG diagram showing these edits. You can click on columns in the diagram to see particular revisions, and on rows to see individual contributors to Wikipedia.

#### Installation
To use this script make sure check the values for 

`$config['proxy_name']=''`

`$config['proxy_port']=''`

in the file `utils.php`. If you are behind a HTTP proxy then you'll need to enter the name and port of your proxy, otherwise leave them as ''.

#### What can go wrong
Apart from the fact that the SVG may take a while to render, the code is sensitive to changes in the XML returned by Wikipedia. The history of edits is recorded in the XML file returned by

`http://en.wikipedia.org/wiki/Special:Export/[page title]?history`

The "wiki" namespace version changes over time (it is currently 0.5, i.e. `http://www.mediawiki.org/xml/export-0.5/`). This is hard-coded in the file `history.php`, and will need to be changed if Wikipedia increments this version of the namespace. You can inspect the current namespace version here: https://en.wikipedia.org/w/index.php?title=Special:Export&action=submit
