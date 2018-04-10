<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 09.04.18
 * Time: 15:42
 */
declare(strict_types = 1);

namespace Alpipego\GhCp;

use Firebase\JWT\JWT;

class GitHub
{
    const API = 'https://api.github.com';
    const ACCEPT_HEADER = 'application/vnd.github.machine-man-preview+json';
    private $userAgent;
    private $privateKey;
    private $appID;
    private $token = null;

    public function __construct()
    {
        $this->userAgent  = (string)apply_filters('ghcp/user_agent', '');
        $this->appID      = (int)apply_filters('ghcp/app_id', '');
        $this->privateKey = (string)apply_filters('ghcp/private_key', '');
    }

    public function getUrl()
    {
        return self::API;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function connect() : self
    {
        $this->token = $this->token ?? $this->jwt();

        return $this;
    }

    private function jwt() : string
    {
        if (time() < get_transient('timeout_ghcp_token')) {
            if ($token = get_transient('ghcp_token')) {
                return $token;
            }
        }

        $jwt = JWT::encode([
            'iat' => (int)time(),
            'exp' => time() + 600,
            'iss' => $this->appID,
        ], $this->privateKey, 'RS256');

        $headers = [
            'Authorization' => 'Bearer ' . $jwt,
            'Accept'        => self::ACCEPT_HEADER,
            'User-Agent'    => $this->userAgent,
            'Time-Zone'     => 'Zulu',
        ];

        $req = \Requests::get(self::API . '/integration/installations', $headers);
        if ((int)$req->status_code !== 200) {
            return ''; // TODO handle error
        }

        $body = json_decode($req->body, true);
        if (count($body) === 0) {
            return ''; // TODO handle error
        }

        $tokenReq = \Requests::post($body[0]['access_tokens_url'], $headers);
        if ((int)$tokenReq->status_code !== 201) {
            return ''; // TODO handle error
        }

        $ghcpToken = json_decode($tokenReq->body, true);
        set_transient('ghcp_token', $ghcpToken['token'], time() - strtotime($ghcpToken['expires_at']));

        return $ghcpToken['token'];
    }

    public function buildUrl(string $route, ... $params)
    {
        return self::API . vsprintf($route, $params);
    }

    public function getUserAgent()
    {
        return $this->userAgent;
    }
}
