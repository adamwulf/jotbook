<?
/**
 * an RSS Item
 * @see http://blogs.law.harvard.edu/tech/rss#ltcategorygtSubelementOfLtitemgt
 */

 
abstract class RSSItem{


	public function __construct(){
	}


	/**
	 * the title of this item
	 */
	abstract public function getTitle();
	
	/**
	 * the link to the content online
	 */
	abstract public function getLink();
	
	/**
	 * the html encoded description
	 */
	abstract public function getDescription();
	
	/**
	 * the email address of the author
	 */
	abstract public function getAuthor();

	/**
	 * returns a list of RSSCategories
	 */
	abstract public function getCategories();
	
	/**
	 * the url to the page that has comments
	 */
	abstract public function getComments();
	
	/**
	 * the enclosure for this item
	 */
	abstract public function getEnclosure();
	
	/**
	 * the globally unique identifier for this item
	 */
	abstract public function getGUID();
	
	/**
	 * datetime in the RFC #733 (NIC #41952) format
	 * @see http://asg.web.cmu.edu/rfc/rfc822.html
	 */
	abstract public function getPubDate();
	
	/**
	 * the source of this item
	 * Its value is the name of the RSS channel that the item came from,
	 * derived from its <title>. It has one required attribute, url, 
	 * which links to the XMLization of the source.
	 * @see http://blogs.law.harvard.edu/tech/rss#ltsourcegtSubelementOfLtitemgt
	 */
	abstract public function getSource();
	
	/**
	 * the visitor pattern
	 */
	 public function execute(RSSVisitor $r){
		 $v->visit($this);
	 }
}


?>