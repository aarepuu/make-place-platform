<?php
/** The root page of our application, all Pages subclass this */
class Page extends SiteTree {

    /** Custom fields every page will have */
    private static $db = array(
    );
    
    /** Custom one-to-one relations every page has */
    private static $has_one = array(
    );
    
    /** Custom one-to-many relations every page has */
    private static $has_many = array(
    );
    
    
    /** Whether the page should show the sidebar option in the CMS (set in subclasses) */
    public static $allows_sidebar = true;
    
    
    
    /** Whether the rendered page should fill the screen */
	public static $page_fills_screen = true;
    
    public function getShouldFillScreen() {
		$class = get_class($this);
		return ($class::$page_fills_screen) ? "fill" : "";
	}
    
    
    /** Get the pages in the breadcrums */
    public function getSitetree() {
		
		$page = $this->data();
		
		$list = ArrayList::create(array($page));
		
		while ($page->ParentID != null) {
			$page = $page->Parent();
			$list->push($page);
		}
		
		$list = $list->reverse();
		
		return $list->renderWith("Breadcrumbs");
	}
    
    
    
    /** Customise CMS fields */
    public function getCMSFields() {
        $fields = parent::getCMSFields();
        
        // Add custom fields to edit the page
        
        return $fields;
    }
}

/**
 * The root Controller of our application, all Controllers should subclass this.
 * By default, Page.ss will be used to render the controller into html, then Layout.Page.ss will be
 * used to render the content of the page. Page subclasses will attempt to use a template of their classname e.g. CalendarPage.ss will render a CalendarPage
 */
class Page_Controller extends ContentController {

    /**
     * An array of actions that can be accessed via a request. Each array element should be an action name, and the
     * permissions or conditions required to allow the user to access it.
     *
     * <code>
     * array (
     *     'action', // anyone can access this action
     *     'action' => true, // same as above
     *     'action' => 'ADMIN', // you must have ADMIN permissions to access this action
     *     'action' => '->checkAction' // you can only access this action if $this->checkAction() returns true
     * );
     * </code>
     *
     * @var array
     */
    private static $allowed_actions = array ();


    /** Called when an instance is created */
    // public function init() {
    //
    //     // Call super
    //     parent::init();
    //
    //     // Custom code called whenever a page is rendered
    //     // ...
    //
    // }
}
