<?php namespace Morningtrain\WpNetsEasy\Classes\Payment;

use Morningtrain\PHPLoader\Loader;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class WebhookHandler {

    public static string $version = 'v1';
    public static string $namespace = 'morningtrain/nets-easy';
    public static string $resource_name = 'webhooks/(?P<webhook>[a-z0-9.]+)';

    protected static array $webhooks = [];

    public static function init()
    {
        static::registerWebhooks();

        add_action('rest_api_init', [static::class, 'registerRestRoute']);
    }

    protected static function registerWebhooks() {
        Loader::create(__DIR__ . '/Webhooks')->isA(Webhook::class)->callStatic('register');
    }

    /**
     * Regsiter a webhook
     *
     * @param string $className Classname shall be subclass to Morningtrain\WpNetsEasy\Classes\Payment\Webhooks\Webhook
     * @return void
     */
    public static function registerWebhook(string $className) {
        if(!is_a($className, Webhook::class, true)) {
            return;
        }

        static::$webhooks[$className::getEventName()] = $className;
    }

    public static function registerRestRoute() {
        register_rest_route(
            static::getNamespace() . '/' . static::getVersion(),
            static::getResourceName(),
            [
                'methods' => 'POST',
                'callback' => [static::class, 'handle'],
                'permission_callback' => [static::class, 'checkPermission'],
                'args' => [
                    'webhook' => [
                        'validate_callback' => [static::class, 'validateWebhookArg']
                    ]
                ]
            ],
            true
        );
    }

    public static function checkPermission(WP_REST_Request $request) : bool
    {
        return static::getAuthKey() === $request->get_header('Authorization');
    }

    public static function getAuthKey() : string
    {
        return wp_hash(site_url());
    }

    public static function validateWebhookArg(string $param, WP_REST_Request $request, string $key) : bool
    {
        $webhooks = static::getWebhooks();

        return isset($webhooks[$param]);
    }

    public static function handle(WP_REST_Request $request)
    {
        $webhookName = $request->get_param('event');

        if($bypass = apply_filters("morningtrain/nets-easy/webhook/{$webhookName}/bypass", false)) {
            return $bypass;
        }

        if(empty(static::getWebhooks()[$webhookName])) {
            return new WP_Error('no_webhook', __('Invalid webhook', 'n'), ['status' => 404]);
        }

        $webhookClass = static::getWebhooks()[$webhookName];

        $webhook = new $webhookClass(json_decode($request->get_body()));

        if($webhook->isHandled()) {
            // Return status 200 to avoid further calls
            return new WP_Error('webhook_handled', __('Webhook already handled', ''), ['status' => 200]);
        }

        do_action("morningtrain/nets-easy/webhook/{$webhookName}", $webhook, $request);

        if($bypass = $webhook->handle()) {
            return $bypass;
        }

        $webhook->handled();

        return apply_filters("morningtrain/nets-easy/webhook/{$webhookName}/response", new WP_REST_Response(), $webhook);
    }

    public static function getWebhooks() : array
    {
        return static::$webhooks;
    }

    protected static function getNamespace() : string
    {
        return static::$namespace;
    }

    protected static function getVersion() : string
    {
        return static::$version;
    }

    protected static function getResourceName() : string {
        return static::$resource_name;
    }

    protected static function getDecodedResourceName() : string
    {
        return preg_replace_callback('/\(\?P\<(?P<name>[a-z]+)\>.+\)/', function($matches) {
            return '[' . $matches['name'] . ']';
        }, static::$resource_name) ?? static::$resource_name;
    }

    protected static function getPath(array $args = []) : string
    {
        $resource_name = static::getDecodedResourceName();

        if(!empty($args)) {
            foreach($args as $key => $value) {
                $resource_name = str_replace("[{$key}]", $value, $resource_name);
            }
        }

        return static::getNamespace() . '/' . static::getVersion() . '/' . $resource_name;
    }

    public static function getUrl(array $args = []) : string
    {
        return rest_url(static::getPath($args));
    }

}