<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * ผู้ใช้ที่ยังไม่ล็อกอินเข้าหน้าแรกต้องถูกเด้งไปหน้า login (ระบบนี้บังคับล็อกอินทุกหน้ายกเว้น /login)
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }
}
