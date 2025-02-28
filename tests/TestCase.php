<?php

namespace Oddvalue\LaravelDrafts\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Oddvalue\LaravelDrafts\LaravelDraftsServiceProvider;
use Oddvalue\LaravelDrafts\Tests\app\Models\Post;
use Oddvalue\LaravelDrafts\Tests\app\Models\Tag;
use Oddvalue\LaravelDrafts\Tests\app\Models\User;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected $testUser;

    protected $testPost;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Oddvalue\\LaravelDrafts\\Database\\Factories\\' . class_basename(
                $modelName,
            ) . 'Factory',
        );

        $this->setUpDatabase($this->app);
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelDraftsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        config()->set('app.key', 'thisisa32bitkeyforunittests12345');
        config()->set('database.default', 'testing');
    }

    /**
     * Set up the database.
     */
    protected function setUpDatabase(Application $app)
    {
        $app['db']->connection()->getSchemaBuilder()->create('users', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('email');
        });

        $app['db']->connection()->getSchemaBuilder()->create('posts', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('title');
            $table->json('traits')->nullable();
            $table->drafts();
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('post_sections', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('content');
            $table->foreignIdFor(Post::class)->constrained();
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('tags', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('taggables', function (Blueprint $table): void {
            $table->foreignIdFor(Tag::class)->constrained();
            $table->morphs('taggable');
        });

        $app['db']->connection()->getSchemaBuilder()->create('post_tag', function (Blueprint $table): void {
            $table->foreignIdFor(Tag::class)->constrained();
            $table->foreignIdFor(Post::class)->constrained();
        });

        $app['db']->connection()->getSchemaBuilder()->create('soft_deleting_posts', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('title');
            $table->drafts();
            $table->softDeletes();
            $table->timestamps();
        });

        $this->testUser = User::create(['email' => 'test@user.com']);
        Auth::login($this->testUser);
    }
}
