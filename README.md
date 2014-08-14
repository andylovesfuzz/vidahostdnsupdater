vidahostdnsupdater
==================

Updates DNS entries via the Vidahost 'Custom DNS Management' page.

I recently moved house and had to change ISP. The new ISP does not supply static IP addresses, so I was using a dynamic DNS service. Unfortunately they forgot to renew their domains and that stopped working. 

So I decided to use one of my own domains, and the DNS for those is hosted by Vidahost. They don't supply an API and I don't have any DNS servers myself. So I made a script to update the record I want via scraping the website. It is very fragile and will probably break quite soon.

This might be useful to someone.

There's no autoloading, so your script should look something like this:

    <?php
        
        require_once 'tools.php';
        require_once 'handle.php';
        require_once 'api.php';
        
        $handle = new handleAwkwardSiteWithoutAPI;
        $api = new API($handle);
        
        $api->login('your user name', 'your password');
        $api->setRecord(
            'yourdomain.com', 
            'record.to.edit.yourdomain.com', 
            '1.2.3.4', // IPv4 IP
            'A',       // Type
            '86400'    // TTL
        );
