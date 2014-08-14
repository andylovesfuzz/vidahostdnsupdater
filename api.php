<?php

    class API 
    {
        protected $handle = null;
        
        protected $loggedIn = false;
        
        public function __construct(handleAwkwardSiteWithoutAPI $handle) 
        {
            $this->handle = $handle;
        }
        
        public function login($username, $password) 
        {
            $this->handle->parseLoginPage();
            
            $this->loggedIn = $this->handle->attemptLogin($username, $password);

            return $this->loggedIn;
        }
        
        public function setRecord($domain, $record, $content, $type = 'A', $ttl = '86400', $priority = 0)
        {
            // Priority doesn't seem to be implemented on vidahost, so that will be ignored for now.

            $domainId = $this->handle->getDomainId($domain);
            $editPage = $this->handle->openEditPage($domainId);
            $postVars = $this->handle->findDomainRecordsOnPageAsPostVars($editPage, $domainId);

            $postVars = $this->handle->changeRecordValuesInPostVars($postVars, $record, $content, $type, $ttl);

            $this->handle->postRecordChanges($postVars);
        }

        public function logout()
        {
            if ($this->loggedIn) {
                $this->handle->logout();
            }
        }
        
    }