<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 09.04.18
 * Time: 11:41
 */
declare(strict_types = 1);

namespace Alpipego\GhCp;

class RestRoute
{
    private $parser;
    private $secret;
    private $restNamespace;

    public function __construct(PayloadParser $parser)
    {
        $this->parser        = $parser;
        $this->secret        = (string)apply_filters('ghcp/webhook_secret', '');
        $this->restNamespace = (string)apply_filters('ghcp/rest_namespace', 'ghcp');
    }

    public function register()
    {
        register_rest_route($this->restNamespace, 'payload', [
            'methods'  => 'POST',
            'callback' => [$this, 'callback'],
        ]);
    }

    public function callback(\WP_REST_Request $request) : ?\WP_REST_Response
    {
        // Validate API Request
        if ( ! empty($this->secret) && ! hash_equals('sha1=' . hash_hmac('sha1', $request->get_body(), $this->secret), $request->get_header('x_hub_signature'))
        ) {
            return null;
        }

        $this->parser->setBody($request->get_body());

        return rest_ensure_response('ok');
    }
}
