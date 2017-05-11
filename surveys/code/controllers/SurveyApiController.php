<?php

/** ... */
class SurveyApiController extends Controller {
    
    private static $allowed_actions = [
        'index', 'submitSurvey', 'getResponses', 'viewResponse', 'viewSurvey', 'createGeom'
    ];
    
    private static $url_handlers = [
        'index' => 'index',
        'submit' => 'submitSurvey',
        'view' => 'viewSurvey',
        'responses' => 'getResponses',
        'response/$ResponseID' => 'viewResponse',
        'geo' => 'createGeom'
    ];
    
    public function init() {
        
        parent::init();
        
        $surveyId = $this->request->param('SurveyID');
        $this->Survey = Survey::get()->byID($surveyId);
        
        if ($this->Survey == null) {
            return $this->httpError(404, "Survey not found");
        }
        
        return $this;
    }
    
    public function index($request) {
        
        $questions = $this->Survey->getQuestionMap();
        $fields = [];
        foreach ($questions as $handle => $question) {
            $fields[$handle] = $question->sample();
        }
        
        return $this->jsonResponse([
            "name" => $this->Survey->Name,
            "questions" => $fields
        ]);
        
        return $this->Survey->Name . ': index';
    }
    
    public function submitSurvey() {
        
        $errors = [];
        
        // Check the authentication of the request
        $memberId = $this->requestMemberId($errors);
        if ($memberId === null) {
            return $this->jsonResponse($errors, 401);
        }
        
        $redirectBack = $this->bodyVar('RedirectBack') != null;
        $fields = $this->bodyVar('Fields', $errors);
        if (count($errors) > 0) {
            return $this->jsonResponse($errors, 400);
        }
        
        
        // Get the map of handle to question objects
        $questionMap = $this->Survey->getQuestionMap();
        
        
        // Validate each question with the passed value or null
        foreach ($questionMap as $field => $question) {
            $errors = array_merge($errors, $question->validateValue(isset($fields[$field]) ? $fields[$field] : null));
        }
        
        
        // If there were any errors upto this point, return them in a failed response
        if (count($errors) > 0) {
            return $this->jsonResponse($errors, 400);
        }
        
        
        // Let the questions pack their value
        foreach ($fields as $field => $value) {
            $fields[$field] = $questionMap[$field]->packValue($value);
        }
        
        
        // Generate a SurveyResponse & save it
        $response = SurveyResponse::create([
            'SurveyID' => $this->Survey->ID,
            'MemberID' => $memberId,
            'Responses' => $fields
        ]);
        
        
        // Let the questions perform post-create actions
        foreach ($fields as $field => $value) {
            $questionMap[$field]->responseCreated($response, $value);
        }
        
        
        // Save the response
        $response->write();
        
        
        // If not ajax or command-line, redirect back
        // IDEA: refactor to a parameter? e.g. RedirectURL? + check if on site
        if ($redirectBack != null) {
            return $this->redirectBack();
        }
        
        
        return $this->jsonResponse($response->toJson());
    }
    
    public function viewSurvey() {
        
        $memberId = Member::currentUserID();
        $auth = $this->Survey->SubmitAuth;
        
        if ($auth == "Member" && $memberId == null) {
            
            return $this->jsonResponse([
                'title' => "Please log in",
                'content' => "<p>Sorry, you need to log in to do that!</p>"
            ], 401);
        }
        
        return $this->jsonResponse([
            'title' => $this->Survey->Name,
            'content' => $this->Survey->forTemplate()->getValue()
        ]);
    }
    
    public function getResponses() {
        
        $responses = SurveyResponse::get()->filter("SurveyID", $this->Survey->ID);
        
        $memberId = Member::currentUserID();
        $auth = $this->Survey->ViewAuth;
        
        if ($auth == "Member" && $memberId == null) {
            
            return $this->jsonResponse(["You need to log in to do that"], 401);
        }
        
        
        $data = [];
        
        foreach ($responses as $r) {
            $data[] = $r->toJson();
        }
        
        return $this->jsonResponse($data);
    }
    
    public function viewResponse() {
        
        $response = SurveyResponse::get()
            ->filter('ID', $this->request->param('ResponseID'))
            ->filter('SurveyID', $this->Survey->ID)
            ->first();
        
        if ($response == null) {
            return $this->httpError(404);
        }
        
        $member = $response->Member();
        
        $rendered = $this->renderWith("SurveyResponse", [
            "Response" => $response
        ]);
        
        $name = ($member) ? $member->getName() : "Unknown";
        
        return $this->jsonResponse([
            "title" => "$name's Response",
            "body" => $rendered->getValue()
        ]);
    }
    
    public function createGeom() {
        
        $errors = [];
        
        // Fetch the member from the request or fail
        $memberId = $this->requestMemberId($errors);
        
        if ($memberId === null) {
            return $this->jsonResponse($errors, 401);
        }
        
        
        // Get request params or fail
        $questionHandle = $this->bodyVar('question', $errors);
        $geom = $this->bodyVar('geom', $errors);
        $type = $this->bodyVar('type', $errors);
        
        if (count($errors)) {
            return $this->jsonResponse($errors, 400);
        }
        
        // Check question exists on survey
        $questionMap = $this->Survey->getQuestionMap();
        if (!isset($questionMap[$questionHandle])) {
            return $this->jsonResponse([ "Invalid 'question'"], 400);
        }
        
        // Check the question is valid
        $question = $questionMap[$questionHandle];
        if ($question->ClassName != 'GeoQuestion') {
            return $this->jsonResponse([ "Must be a GeoQuestion"], 400);
        }
        
        
        // Create the geo ref & return the errors or new reference
        $ref = GeoRef::makeRef($type, $question->DataType, $geom, $errors);
        if ($ref == null) {
            return $this->jsonResponse($errors, 400);
        }
        
        return $this->jsonResponse($ref->toJson());
    }
    
    
    
    /* Utils */
    public function requestMemberId(&$errors) {
        
        // Try to use api auth first
        if ($this->checkApiAuth()) {
            
            // If authed with the api, get the newly authenticated member
            return Member::currentUserID();
        }
        
        $auth = $this->Survey->SubmitAuth;
        $memberId = Member::currentUserID();
        
        if ($auth === "Member" && $memberId == null) {
            $errors[] = "You need to be logged in to do that";
        }
        
        // Check the security token matches
        $token = $this->bodyVar('SecurityID');
        if ($token == null || $token != (new SecurityToken())->getValue()) {
            $errors[] = "Validation failed, please submit again";
        }
        
        return count($errors) == 0 ? $memberId : null;
    }
    
    public function bodyVar($name, &$errors = []) {
        
        $post = $this->postVar($name);
        if ($post != null) { return $post; }
        
        $json = $this->jsonVar($name);
        if ($json != null) { return $json; }
        
        $errors[] = "Please provide '$name'";
        return null;
    }
    
    public function postVar($name, &$errors = []) {
        
        if ($this->request->postVar($name) != null) {
            return $this->request->postVar($name);
        }
        
        $errors[] = "Please provide '$name'";
        return null;
    }
    
    public function jsonVar($name, &$errors = []) {
        
        if ($this->jsonBody == null) {
            $this->jsonBody = json_decode($this->request->getBody(), true);
        }
        
        if (isset($this->jsonBody[$name])) {
            return $this->jsonBody[$name];
        }
        
        $errors[] = "Please provide '$name'";
        
        return null;
    }
    
    public function getVar($name, &$errors = []) {
        
        if ($this->request->getVar($name) != null) {
            return $this->request->getVar($name);
        }
        
        $errors[] = "Please provide '$name'";
        return null;
    }
    
}
