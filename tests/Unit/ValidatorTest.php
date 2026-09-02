<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Validator;
use Tests\TestCase;

class ValidatorTest extends TestCase
{
    public function testRequiredRuleFailsWhenMissing(): void
    {
        $v = Validator::make(['username' => ''], [
            'username' => 'required',
        ]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('username', $v->errors());
    }

    public function testEmailRule(): void
    {
        $v1 = Validator::make(['email' => 'not-an-email'], ['email' => 'email']);
        $this->assertTrue($v1->fails());

        $v2 = Validator::make(['email' => 'commander@voidstrike.io'], ['email' => 'email']);
        $this->assertTrue($v2->passes());
    }

    public function testMinAndMaxRules(): void
    {
        $v1 = Validator::make(['name' => 'ab'], ['name' => 'min:3']);
        $this->assertTrue($v1->fails());

        $v2 = Validator::make(['name' => 'abcdef'], ['name' => 'max:4']);
        $this->assertTrue($v2->fails());

        $v3 = Validator::make(['name' => 'abcd'], ['name' => 'min:3|max:5']);
        $this->assertTrue($v3->passes());
    }

    public function testConfirmedRule(): void
    {
        $v1 = Validator::make([
            'password' => 'secret123',
            'password_confirmation' => 'mismatch',
        ], [
            'password' => 'confirmed',
        ]);
        $this->assertTrue($v1->fails());

        $v2 = Validator::make([
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ], [
            'password' => 'confirmed',
        ]);
        $this->assertTrue($v2->passes());
    }

    public function testInRule(): void
    {
        $v1 = Validator::make(['chassis' => 'unknown'], ['chassis' => 'in:striker,titan,phantom']);
        $this->assertTrue($v1->fails());

        $v2 = Validator::make(['chassis' => 'striker'], ['chassis' => 'in:striker,titan,phantom']);
        $this->assertTrue($v2->passes());
    }

    public function testAlphaDashRule(): void
    {
        $v1 = Validator::make(['callsign' => 'Apex Viper!'], ['callsign' => 'alpha_dash']);
        $this->assertTrue($v1->fails());

        $v2 = Validator::make(['callsign' => 'Apex_Viper-99'], ['callsign' => 'alpha_dash']);
        $this->assertTrue($v2->passes());
    }
}
