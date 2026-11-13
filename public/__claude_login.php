<?php
// TEMPORARY (Claude screenshot helper) - delete after use
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) { http_response_code(404); exit; }
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$app->instance('request', $request);
$kernel->bootstrap();
$portal = 'learner';
$cookie = App\Http\Middleware\UsePortalSession::cookieName($portal);
config(['session.cookie' => $cookie]);
$session = $app['session']->driver();
$session->setName($cookie);
$session->start();
$user = App\Models\Learner::withoutGlobalScopes()->find((int) ($_GET['id'] ?? 7136));
$session->put('login_learner_' . sha1(Illuminate\Auth\SessionGuard::class), $user->id);
$session->save();
$val = Illuminate\Cookie\CookieValuePrefix::create($cookie, $app['encrypter']->getKey()) . $session->getId();
$enc = $app['encrypter']->encrypt($val, false);
setcookie($cookie, $enc, ['path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
header('Location: ' . ($_GET['to'] ?? '/libraro/learner/home'));
