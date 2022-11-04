<?php

namespace App\Integrations;

use App\Models\Account;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;

class DBCookieJar extends CookieJar
{

    private $account;

    public function __construct(Account $account)
    {
        $this->account = $account;
        $this->load();
    }

    public function __destruct()
    {
        $this->save();
    }

    protected function load()
    {
        if(empty($this->account->credentials['cookies']))
            return;

        $data = $this->account->credentials['cookies'];
        if (is_array($data)) {
            foreach ($data as $cookie)
                $this->setCookie(new SetCookie($cookie));
        } elseif (strlen($data)) {
            throw new \RuntimeException("Invalid cookie data");
        }
    }

    public function save()
    {
        $json = [];
        foreach ($this as $cookie) {
            /** @var SetCookie $cookie */
            if (CookieJar::shouldPersist($cookie,true)) {
                $json[] = $cookie->toArray();
            }
        }

        $this->account->credentials = array_merge($this->account->credentials, ['cookies' => $json]);
        $this->account->save();
    }

}
