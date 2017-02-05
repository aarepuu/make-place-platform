<?php

class MockApiController extends ContentController { }


/** ... */
class JsonApiExtensionTest extends SapphireTest {
    
    public function setUpOnce() {
        parent::setUpOnce();
        
        MockApiController::add_extension('JsonApiExtension');
    }
    
    
    public function testExtensionAdded() {
        
        $this->assertTrue(MockApiController::has_extension('JsonApiExtension'));
    }
    
    public function testJsonResponse() {
        
        $object = MockApiController::create();
        
        $response = $object->jsonResponse(['Hello']);
        
        $this->assertEquals(["Content-Type" => "application/json"], $response->getHeaders());
        $this->assertEquals("[\"Hello\"]", $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function testJsonResponseStatusCode() {
        
        $object = MockApiController::create();
        
        $response = $object->jsonResponse(['Hello'], 404);
        
        $this->assertEquals(404, $response->getStatusCode());
    }
    
    public function testFormattedJsonResponse() {
        
        $object = MockApiController::create();
        
        $response = $object->formattedJsonResponse('Hello, World!', 'test message');
        
        $expected = '{"meta":{"success":true,"messages":["test message"]},"data":"Hello, World!"}';
        
        $this->assertEquals(["Content-Type" => "application/json"], $response->getHeaders());
        $this->assertEquals($expected, $response->getBody());
    }
}
