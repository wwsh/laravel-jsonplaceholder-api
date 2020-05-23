<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePosts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'posts',
            function (Blueprint $table) {
                $table->id();
                $table->integer('user_id');
                $table->foreign('user_id')->references('id')->on('users');
                $table->string('title');
                $table->text('body');
                $table->timestamps();
            }
        );

        // Create a fulltextsearch for the "title" column manually (Laravel lacks support)
        DB::statement('ALTER TABLE posts ADD COLUMN fulltextsearch TSVECTOR');
        DB::statement("UPDATE posts SET fulltextsearch = to_tsvector('english', title)");
        DB::statement('CREATE INDEX fulltextsearch_gin ON posts USING GIN(fulltextsearch)');
        DB::statement(
            "CREATE TRIGGER ts_fullsearchtext BEFORE INSERT OR UPDATE ON posts FOR EACH ROW EXECUTE PROCEDURE tsvector_update_trigger('fulltextsearch', 'pg_catalog.english', 'title')"
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TRIGGER IF EXISTS tsvector_update_trigger ON posts');
        DB::statement('DROP INDEX IF EXISTS fulltextsearch_gin');
        DB::statement('ALTER TABLE posts DROP COLUMN fulltextsearch');

        Schema::dropIfExists('posts');
    }
}
