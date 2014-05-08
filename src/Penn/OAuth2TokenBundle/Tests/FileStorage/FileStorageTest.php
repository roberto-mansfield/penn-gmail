<?php

use Penn\OAuth2TokenBundle\Storage\FileStorage;

class FileStorageTest extends PHPUnit_Framework_TestCase {
    
    /**
     * Simple read test
     */
    public function testGetFile() {
        
        $testReadPath = "/tmp/file-storage-test-read";
        $fileContents = "This is the content in my file!"; 
        
        if ( !file_put_contents($testReadPath, $fileContents) ) {
            throw new Exception("Error creating test file: $testReadPath");
        }
        
        $storage = new FileStorage($testReadPath);
        
        $this->assertEquals($fileContents, $storage->get());

        unlink($testReadPath);
    }
    
    
    /**
     * Simple write test
     */
    public function testSaveFile() {

        $testWritePath = "/tmp/file-storage-test-read";
        $fileContents  = "This is the content in my file!";
        
        $storage = new FileStorage($testWritePath);
        $storage->save($fileContents);
        
        $this->assertEquals($fileContents, file_get_contents($testWritePath));
        
        unlink($testWritePath);        
    }
    
    /**
     * Test files/paths which are not writable
     */
    public function testFilePathException() {
        
        $testPath = "/etc/file-storage-test.txt";
        
        try {
            $storage = new FileStorage($testPath);
        } catch (Exception $e) {
            return;
        }
        $this->fail("Expected an exception initializing object to $testPath (are you running as root?)");
    }
    
    /**
     * Test save() exception when file is locked for write
     */
    public function testSaveFileLockWrite() {
        
        $testWritePath = "/tmp/file-storage-test-write";
        $fileContents  = "This is the content in my file!";
        
        if ( !file_put_contents($testWritePath, $fileContents) ) {
            throw new Exception("Error creating test file: $testWritePath");
        }        
        
        $fh = fopen($testWritePath, 'w');
        if ( !$fh ) {
            throw new Exception("Error opening test file for write: $testWritePath");
        }
        
        if ( !flock($fh, LOCK_EX) ) {
            throw new Exception("Error locking test file for write: $testWritePath");
        }
        
        $storage = new FileStorage($testWritePath);
        
        try {
            $storage->save($fileContents);
        } catch (Exception $e) {
            return;
        }
        
        fclose($fh);
        unlink($testWritePath);
        $this->fail("Expected an exception trying to save to a locked file");
    }

    /**
     * Test save() exception when file is locked for read
     */
    public function testSaveFileLockRead() {
    
        $testWritePath = "/tmp/file-storage-test-write";
        $fileContents  = "This is the content in my file!";
    
        if ( !file_put_contents($testWritePath, $fileContents) ) {
            throw new Exception("Error creating test file: $testWritePath");
        }
    
        $fh = fopen($testWritePath, 'r');
        if ( !$fh ) {
            throw new Exception("Error opening test file for write: $testWritePath");
        }
    
        if ( !flock($fh, LOCK_SH) ) {
            throw new Exception("Error locking test file for share: $testWritePath");
        }
    
        $storage = new FileStorage($testWritePath);
    
        try {
            $storage->save($fileContents);
        } catch (Exception $e) {
            return;
        }
    
        fclose($fh);
        unlink($testWritePath);
        $this->fail("Expected an exception trying to save to a locked file");
    }    
    
    
    /**
     * Test get() exception when file is locked for write
     */
    public function testGetFileLockWrite() {
        
        $testWritePath = "/tmp/file-storage-test-write";
        $fileContents  = "This is the content in my file!";
        
        if ( !file_put_contents($testWritePath, $fileContents) ) {
            throw new Exception("Error creating test file: $testWritePath");
        }        
        
        $fh = fopen($testWritePath, 'w');
        if ( !$fh ) {
            throw new Exception("Error opening test file for write: $testWritePath");
        }
        
        if ( !flock($fh, LOCK_EX) ) {
            throw new Exception("Error locking test file for write: $testWritePath");
        }
        
        $storage = new FileStorage($testWritePath);
        
        try {
            $data = $storage->get();
        } catch (Exception $e) {
            return;
        }
        
        fclose($fh);
        unlink($testWritePath);
        $this->fail("Expected an exception trying to get() a locked file");
    }

    /**
     * Test get() when file is locked for read
     */
    public function testGetFileLockRead() {
    
        $testWritePath = "/tmp/file-storage-test-write";
        $fileContents  = "This is the content in my file!";
    
        if ( !file_put_contents($testWritePath, $fileContents) ) {
            throw new Exception("Error creating test file: $testWritePath");
        }
    
        $fh = fopen($testWritePath, 'r');
        if ( !$fh ) {
            throw new Exception("Error opening test file for write: $testWritePath");
        }
    
        if ( !flock($fh, LOCK_SH) ) {
            throw new Exception("Error locking test file for share: $testWritePath");
        }
    
        $storage = new FileStorage($testWritePath);
        $this->assertEquals($fileContents, $storage->get());
    
        fclose($fh);
        unlink($testWritePath);
    }    
    

}