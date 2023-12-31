<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Filament\Navigation\UserMenuItem;
use Illuminate\Support\ServiceProvider;
use io3x1\FilamentUser\Resources\UserResource;
use BezhanSalleh\FilamentShield\Resources\RoleResource;

class FilamentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {
        Filament::serving(function() {

            if (auth()->user()) {
                if (auth()->user()->is_admin) {

                    Filament::registerUserMenuItems([
                        UserMenuItem::make()
                        ->label(auth()->user()->name)
                        ,
                        UserMenuItem::make()
                            ->label('Manage Users')
                            ->url(UserResource::getUrl())
                            ->icon('heroicon-s-users'),
                        UserMenuItem::make()
                            ->label('Manage Roles')
                            ->url(RoleResource::getUrl())
                            ->icon('heroicon-s-cog'),

                    ]);

                }
            }
        });
    }
}
