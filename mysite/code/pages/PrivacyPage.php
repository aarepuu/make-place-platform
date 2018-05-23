<?php

/** A page to show the privacy policy */
class PrivacyPage extends Page {
    
    private static $db = [
        'EffectiveDate' => 'Date',
        'ContactInfo' => 'HTMLText',
        'SummaryContent' => 'HTMLText'
    ];
    
    private static $defaults = [
        'ShowInMenus' => 0,
        'ShowInSearch' => 0
    ];
    
    public function getCMSFields() {
        
        $fields = parent::getCMSFields();
        
        $fields->addFieldsToTab('Root.Privacy', [
            DateField::create('EffectiveDate', 'When the terms were last updated'),
            HtmlEditorField::create('ContactInfo', 'Contact Info'),
            HtmlEditorField::create('SummaryContent', 'Summary Info')
        ]);
        
        $fields->removeByName('Content');
        
        return $fields;
    }
}

class PrivacyPage_Controller extends Page_Controller {
    
    public function getContactEmailLink() {
        $email = CONTACT_EMAIL;
        return '<a href="mailto:'.$email.'?subject=make.place">'.$email.'</a>';
    }
    
    public function getTermsLink() {
        $url = Director::absoluteURL('/terms');
        return '<a href="'.$url.'">'.$url.'</a>';
    }
}
