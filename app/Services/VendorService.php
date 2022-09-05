<?php

namespace App\Services;

use App\Models\Credential;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Exception;

class VendorService extends BaseService
{
    protected $stack;
    protected $guzzlehttp;

    public function __construct()
    {
        $this->stack = HandlerStack::create();
        $this->stack->push(new CacheMiddleWare(), 'cache');
        $this->guzzlehttp = new Client(['handler' => $this->stack]);
    }

    protected function client()
    {
        return $this->guzzlehttp;
    }

    public function get($uri, $options)
    {
        try {
            $res =  $this->client()->get($uri, $options);

            return json_decode($res->getBody());
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function post($uri, $options)
    {
        try {
            $res =  $this->client()->post($uri, $options);

            return json_decode($res->getBody());
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getCredential($credentialId = 0)
    {
        $credential = Credential::find($credentialId);

        if (!$credential) {
            return '';
        }

        $expiredAt = $credential->expired_at;
        $currentTimeStamp = Carbon::now()->timestamp;

        if ($expiredAt < $currentTimeStamp) {
            $credential->expired_at = Carbon::now()->addHour(8)->timestamp;
            $config = Config('vendor.' . $credential->key);
            $options = [
                'body' => json_encode([
                    'username' => $config['username'],
                    'password' => $config['password'],
                ])
            ];
            $response = $this->post($config['login_url'], $options);
            $credential->token = $response->token;
            $credential->save();
        }

        return $credential->token;
    }
}
