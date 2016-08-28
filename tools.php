<?php

    class Tools
    {
        const MAX_REDIRECTS = 5;

        protected static $curl = null;

        protected static function getCurl()
        {
            if (is_null(self::$curl)) {
                self::$curl = curl_init();

                /* Set where to store cookies. Todo: make OS agnostic */
                $cookieJarFile = tempnam('/tmp', 'COOKIEJAR');
                curl_setopt(self::$curl, CURLOPT_COOKIEJAR, $cookieJarFile);
                curl_setopt(self::$curl, CURLOPT_RETURNTRANSFER, true);

                /* Enable headers so we can see the cookie stuff */
                // curl_setopt(self::$curl, CURLOPT_HEADER, 1);
            }
            return self::$curl;
        }

        public static function postToURL($url, array $postVars)
        {
            $queryString = http_build_query($postVars);

            $curl = self::getCurl();

            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $queryString);
            //curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $result = curl_exec($curl);
            $curlInfo = curl_getinfo($curl);
            //This line is useful to uncomment when debugging
            //print_r($curlInfo);
            if (!empty($curlInfo['redirect_url'])) {
                return Tools::getFromUrl($curlInfo['redirect_url'], 1);
            } else {
                return $result;
            }
        }

        public static function getFromUrl($url, $redirectNum = 0)
        {
            /*
             * This does the same as file_get_contents(), but uses
             * the cURL library, which means we get session cookie
             * support and we don't have to worry about it.
             */

            if ($redirectNum > self::MAX_REDIRECTS) {
                throw new Exception('Too many redirects.');
            }

            $curl = self::getCurl();

            curl_setopt($curl, CURLOPT_URL, $url);

            $result = curl_exec($curl);
            $curlInfo = curl_getinfo($curl);
            //This line is useful to uncomment when debugging
           // print_r($curlInfo);

            if (!empty($curlInfo['redirect_url'])) {
                return Tools::getFromUrl($curlInfo['redirect_url'], $redirectNum + 1);
            } else {
                return $result;
            }
        }

        public static function extractFromForm($formName, $fieldName, $contents)
        {
            /*
             * This is horrible because HTML is pretty hard to parse properly. Normally because
             * you can't rely on it being valid.
             *
             * XHTML did have /some/ uses!
             *
             * Todo: change strpos for a proper regex (<form ... name="{$formName}" ...>)
             */

            if ($formName == '') {
                $pos = 0;
            } else {
                $pos = strpos($formName, $contents);
                if ($pos === 0) {
                    throw new Exception('Unable to find form on page: '.$formName);
                }
            }

            preg_match('/name=\"'.$fieldName.'\" value="([^"]*)"/i', $contents, $matches, PREG_OFFSET_CAPTURE, $pos);
            if (count($matches) == 2) {
                return $matches[1][0];
            } else {
                throw new Exception('Unable to find '.$fieldName.' in '.$formName);
            }
        }

    }
