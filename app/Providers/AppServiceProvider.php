<?php
namespace App\Providers;

use App\Services\ChangeLogger;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
 /**
  * Register any application services.
  */
 public function register(): void
 {
  //
 }

 /**
  * Bootstrap any application services.
  */
 public function boot(): void
 {
  // Change log: catches every model's create/update/delete admin-wide, with
  // no per-model wiring needed. See App\Services\ChangeLogger.
  Event::listen('eloquent.created: *', function ($event, $data) {
   ChangeLogger::recordModelEvent('created', $data[0]);
  });

  Event::listen('eloquent.updated: *', function ($event, $data) {
   ChangeLogger::recordModelEvent('updated', $data[0]);
  });

  Event::listen('eloquent.deleted: *', function ($event, $data) {
   ChangeLogger::recordModelEvent('deleted', $data[0]);
  });
 }
}
