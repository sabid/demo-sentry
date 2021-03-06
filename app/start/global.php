<?php

/*
|--------------------------------------------------------------------------
| Register The Laravel Class Loader
|--------------------------------------------------------------------------
|
| In addition to using Composer, you may use the Laravel class loader to
| load your controllers and models. This is useful for keeping all of
| your classes in the "global" namespace without Composer updating.
|
*/

ClassLoader::addDirectories(array(

	app_path().'/commands',
	app_path().'/controllers',
	app_path().'/models',
	app_path().'/database/seeds',
	app_path().'/swipe',

));

/*
|--------------------------------------------------------------------------
| Application Error Logger
|--------------------------------------------------------------------------
|
| Here we will configure the error logger setup for the application which
| is built on top of the wonderful Monolog library. By default we will
| build a basic log file setup which creates a single file for logs.
|
*/

Log::useFiles(storage_path().'/logs/laravel.log');

/*
|--------------------------------------------------------------------------
| Application Error Handler
|--------------------------------------------------------------------------
|
| Here you may handle any errors that occur in your application, including
| logging them or displaying custom views for specific errors. You may
| even register several error handlers to handle different types of
| exceptions. If nothing is returned, the default error view is
| shown, which includes a detailed stack trace during debug.
|
*/

App::error(function(Cartalyst\Sentry\Checkpoints\NotActivatedException $exception, $code)
{
	return Redirect::to('wait')
		->withErrors($exception->getMessage());
});

App::error(function(Cartalyst\Sentry\Checkpoints\SwipeIdentityException $exception, $code)
{
	$code = $exception->getCode();
	$response = $exception->getResponse();
	$user = $exception->getUser();

	switch ($code)
	{
		case NEED_REGISTER_SMS:
			return Redirect::to('swipe/sms/register')
				->withInput();

		case NEED_REGISTER_SWIPE:
			return Redirect::to('swipe/register')
				->withInput()
				->with('swipe_login', $user->getUserLogin())
				->with('swipe_code', $response->getUserSwipeActivationCode());

		case RC_SWIPE_REJECTED:
			return Redirect::to('login')
				->withErrors('Swipe Identity authentication rejected by device.');
			break;

		case RC_SMS_DELIVERED:
			return Redirect::to('swipe/sms/code')
				->withInput();

		case RC_ERROR:
		case RC_APP_DOES_NOT_EXIST:
			return Redirect::to('login')
				->withErrors($e->getMessage());
	}

	dd($message);
});

App::error(function(Cartalyst\Sentry\Checkpoints\ThrottlingException $exception, $code)
{
	$free = $exception->getFree()->format('d M, h:i:s a');

	switch ($exception->getType())
	{
		case 'global':
			$message = "Our site appears to be spammed. To give eveything a chance to calm down, please try again after {$free}.";
			break;

		case 'ip':
			$message = "Too many unauthorized attemps have been made against your IP address. Please wait until {$free} before trying again.";
			break;

		case 'user':
			$message = "Too many unauthorized attemps have been made against your account. For your security, your account is locked until {$free}.";
			break;
	}

	return Redirect::to('login')
		->withErrors($message);
});

App::error(function(Exception $exception, $code)
{
	Log::error($exception);
});

/*
|--------------------------------------------------------------------------
| Maintenance Mode Handler
|--------------------------------------------------------------------------
|
| The "down" Artisan command gives you the ability to put an application
| into maintenance mode. Here, you will define what is displayed back
| to the user if maintenance mode is in effect for the application.
|
*/

App::down(function()
{
	return Response::make("Be right back!", 503);
});

/*
|--------------------------------------------------------------------------
| Require The Filters File
|--------------------------------------------------------------------------
|
| Next we will load the filters file for the application. This gives us
| a nice separate location to store our route and application filter
| definitions instead of putting them all in the main routes file.
|
*/

require app_path().'/filters.php';
