<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Localization\LocaleMiddleware;
use App\Localization\Translator;
use Tests\TestCase;

class HttpEndpointsTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $basePath = dirname(__DIR__, 2);
        Translator::init($basePath . '/resources/lang');
        View::init($basePath . '/resources/views');
        Session::getInstance()->start();

        $this->router = new Router();
        $this->router->use(LocaleMiddleware::class);

        $router = $this->router;
        require $basePath . '/routes/web.php';
        require $basePath . '/routes/api.php';
    }

    public function testPublicWebPagesReturn200(): void
    {
        $pages = [
            '/' => 'VOIDSTRIKE',
            '/play' => 'game-canvas',
            '/leaderboard' => 'Galactic Ranking Matrix',
            '/leaderboard/weekly' => 'Weekly Season',
            '/challenges' => 'Challenges',
            '/achievements' => 'Achievements',
            '/login' => 'Sign In',
            '/register' => 'Enlist Now',
            '/forgot-password' => 'Lost Access Key?',
        ];

        foreach ($pages as $uri => $expectedContent) {
            $req = Request::create('GET', $uri);
            $res = $this->router->dispatch($req);

            $this->assertEquals(200, $res->getStatusCode(), "Endpoint {$uri} should return HTTP 200");
            $this->assertStringContains($expectedContent, $res->getContent(), "Endpoint {$uri} missing expected content '{$expectedContent}'");
        }
    }

    public function testApiMatchStartHandshake(): void
    {
        $req = Request::create('POST', '/api/match/start', [], [
            'vehicle' => 'striker',
            'arena' => 'neon_core',
            'difficulty' => 'normal',
        ]);

        $res = $this->router->dispatch($req);
        $this->assertEquals(200, $res->getStatusCode());

        $data = json_decode($res->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertNotNull($data['handshake']['run_token']);
        $this->assertEquals('striker', $data['handshake']['vehicle']);
        $this->assertEquals('neon_core', $data['handshake']['arena']);
    }

    public function testBilingualFrenchSwitching(): void
    {
        $req = Request::create('GET', '/?lang=fr');
        $res = $this->router->dispatch($req);

        $this->assertEquals(200, $res->getStatusCode());
        // French dictionary check
        $this->assertStringContains('Modes de Jeu', $res->getContent());
        $this->assertStringContains('Genèse du Néant', $res->getContent());
    }
}
