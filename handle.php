<?php

    class handleAwkwardSiteWithoutAPI
    {
        protected $token = '';
        protected $baseURL = 'https://my.vidahost.com';

        public function getToken()
        {
            if (empty($this->token)) {
                throw new Exception("Can't get token before parsing the login page!");
            }
            return $this->token;
        }

        public function parseLoginPage()
        {
            $loginUrl = $this->baseURL.'/clientarea.php';
            $contents = file_get_contents($loginUrl);

            $this->token = Tools::extractFromForm('frmLogin', 'token', $contents);
        }

        public function attemptLogin($username, $password)
        {
            $postVars = array(
                'username' => $username,
                'password' => $password,
                'token' => $this->getToken(),
            );

            $loginUrl = $this->baseURL.'/dologin.php';

            $result = Tools::postToURL($loginUrl, $postVars);

            // There's no "easy" way to see if we're logged in or not.
            // Todo: make this less shit.
            return (
                strpos($result, 'Welcome to our client area') !== false &&
                strpos($result, 'frmLogin') === false);
        }

        public function getDomainId($domain)
        {
            $result = Tools::getFromURL($this->baseURL.'/customdns.php');

            /* We have to find the domain ID to do *another* POST to get to the right page. */
            preg_match('/<option value="([0-9]+)">'.$domain.'<\/option>/', $result, $matches);
            if (!isset($matches[1])) {
                throw new Exception('Unable to find domain on page');
            }

            return $matches[1];
        }

        public function openEditPage($domainId)
        {
            $postVars = array(
                'token' => $this->getToken(),
                'domainid' => $domainId,
            );

            $result = Tools::postToUrl($this->baseURL.'/customdns.php', $postVars);

            return $result;
        }

        public function findDomainRecordsOnPageAsPostVars($page, $domainId)
        {
            preg_match_all('/<input name="name-([0-9]+)" type="text"/', $page, $matches);

            if (count($matches[1]) == 0) {
                return array();
            }
            
            $recordIds = $matches[1];

            /* Todo: make this more robust */
            $return = array();
            foreach($recordIds as $recordId) {
            
                preg_match('/<input name="name-'.$recordId.'" type="text" value="([^"]+)"/', $page, $matches);
                $return['name-'.$recordId] = $matches[1];
                
                preg_match('/<input type="text" name="content-'.$recordId.'" value="([^"]+)"/', $page, $matches);
                $return['content-'.$recordId] = $matches[1];
                
                preg_match('/<input type="text" name="ttl-'.$recordId.'" value="([^"]+)"/', $page, $matches);
                $return['ttl-'.$recordId] = $matches[1];
                
                preg_match('/<select name="type-'.$recordId.'" .*<option value="([^"]+)" selected>/s', $page, $matches);
                $return['type-'.$recordId] = $matches[1];
            }

            $return['domainid'] = $domainId;
            $return['doupdate'] = 'do';
            $return['token'] = $this->getToken();
            $return['updatedns'] = 'Update Zone';

            return $return;
        }
        
        public function changeRecordValuesInPostVars($postVars, $record, $content, $type, $ttl)
        {
            $recordId = 0;
            foreach($postVars as $key=>$val)
            {
                if (substr($key, 0, 4) != 'name') {
                    continue;
                }
                
                if ($val == $record) {
                    $recordId = substr($key, 5);
                    break;
                }
            }
            
            if ($recordId == 0) {
                throw new Exception('Unable to find record: '.$record);
            }
            
            $postVars['content-'.$recordId] = $content;
            $postVars['ttl-'.$recordId] = $ttl;
            $postVars['type-'.$recordId] = $type;

            return $postVars;
        }
        
        public function postRecordChanges($postVars) 
        {
            $result = Tools::postToUrl($this->baseURL.'/customdns.php', $postVars);
            return $result;
        }

    }

