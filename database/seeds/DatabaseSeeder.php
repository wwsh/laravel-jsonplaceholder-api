<?php

use App\Post;
use App\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        factory(App\User::class, 50)->create()->each(
            function (User $user) {
                $posts = factory(App\Post::class, 20)->make();

                $user->posts()->saveMany($posts);

                $posts->each(
                    function (Post $post) {
                        $post->comments()->saveMany(
                            factory(App\Comment::class, 10)->make()
                        );
                    }
                );

            }
        );
    }
}
