<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class HomeTest extends DuskTestCase
{
    /**
     * A Dusk test example.
     *
     * @return void
     */

    public function testHomeNotAuth()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertSee('Não autenticado');
            $browser->screenshot('home-no-user');
        });
    }

    public function testHomeStdUser()
    {
        $this->browse(function ($browser) {

            $browser->loginAs(SELF::getUser())
                ->visit('/')
                ->assertSee('dusk test')
                ->assertSourceMissing(config('laravel-tools.prefix'));
            $browser->screenshot('home-user-logged-in');
        });
    }

    /**
     * Pressupoe que o menu do laravel-tools está ativo
     */
    public function testHomeAdmin()
    {
        $this->browse(function ($browser) {
            $browser->loginAs(SELF::getUser('admin'))
                ->visit('/')
                ->assertSourceHas(config('laravel-tools.prefix'));
            $browser->screenshot('home-admin-logged-in');
        });
    }

    public function testHome()
    {
        $this->browse(function ($browser) {
            $browser->loginAs(SELF::getUser())
                ->visit('/')
                ->clickAndWaitForReload('#menu .navbar-brand')
                ->assertSee('dusk test');
        });
    }
}
