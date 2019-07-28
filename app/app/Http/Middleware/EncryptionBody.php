<?php
namespace App\Http\Middleware;
use Closure;
use Elliptic\EC;

class EncryptionBody
{
    protected $ec;
    protected $cipher = 'AES-256-CBC';

    protected $except = [
        //
    ];

    public function __construct () {
        $this->ec = new EC('secp256k1');
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $bobPublic = $request->header('X-Client-Key');

        if (empty($bobPublic)) {
            if ($request->method() === 'POST') {
                $response = response('No Encrypt', 400);
                return $response;
            } else {
                return $next($request);
            }
        }

        $aliceKey = $request->session()->get('private');
        if (empty($aliceKey)) {
            $alice = $this->ec->genKeyPair();
            $request->session()->put('private', $alice->getPrivate()->toString('hex'));
        } else {
            $alice = $this->ec->keyFromPrivate($request->session()->get('private'));
        }
        try {
            $alicePublic = $alice->getPublic(true, 'hex');
            $bob = $this->ec->keyFromPublic($bobPublic, 'hex');
            $shared = $alice->derive($bob->getPublic());
            $key = $shared->toString(16);
            $key = substr(hex2bin($key), 0, 32);
            $content = $request->getContent();
            if (!empty($content)) {
                $text = $this->decrypt($content, $key);
                $json = \json_decode($text, true);
                if ($json) {
                    $request->replace($json);
                }
            }
            $response = $next($request);
            $response->header('X-Server-Key', $alice->getPublic(true, 'hex'));

            $content = ob_get_clean() . $response->getContent();
            if ($response->status() === 200) {
                $response->setContent($this->encrypt($content, $key));
            }
            return $response;
        } catch (\Exception $e) {
            return response('Key error', 400);
        }
    }

    private function encrypt ($data, $key) {
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encrypted = openssl_encrypt($data, $this->cipher, $key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $encrypted, $key, true);
        return base64_encode($iv . $hmac . $encrypted);
    }

    private function decrypt ($data, $key) {
        $c = base64_decode($data);
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = substr($c, 0, $ivLength);
        $hmac = substr($c, $ivLength, 32);
        $encrypted = substr($c, $ivLength + 32);
        $decrypted = openssl_decrypt($encrypted, $this->cipher, $key, OPENSSL_RAW_DATA, $iv);
        $calculatedHmac = hash_hmac('sha256', $decrypted, $key, true);
        if (is_string($hmac) && hash_equals($hmac, $calculatedHmac)) {
            return $decrypted;
        }
        return '';
    }
}
