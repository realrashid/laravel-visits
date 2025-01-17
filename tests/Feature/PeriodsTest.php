<?php

namespace awssat\Visits\Tests\Feature;

use awssat\Visits\Tests\TestCase;
use Illuminate\Support\Carbon;
use awssat\Visits\Tests\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;


class PeriodsTest extends TestCase
{
    use RefreshDatabase;

    protected $redis;

    public function setUp()
    {
        parent::setUp();
    }

    /** @test * */
    public function x_hours_periods()
    {
        config()->set('visits.periods', ['3hours']);

        Carbon::setTestNow( 
            Carbon::now()->endOfxHours(3) 
        );

        $post = Post::create()->fresh();

        visits($post)->increment();

        $this->assertEquals([1, 1], [
            visits($post)->count(),
            visits($post)->period('3hours')->count(),
        ]);

        sleep(1);

        $this->assertEquals([1, 0], [
            visits($post)->count(),
            visits($post)->period('3hours')->count(),
        ]);
    }

    /** @test */
    public function day_test()
    {
        $time = Carbon::now();

        Carbon::setTestNow($time->endOfDay());

        $post = Post::create()->fresh();

        visits($post)->increment();

        //it should be there fo breif of time
        $this->assertEquals([1, 1, 1], [
            visits($post)->count(),
            visits($post)->period('day')->count(),
            visits('awssat\Visits\Tests\Post')->period('day')->count(),
        ]);

        //time until redis delete periods
        $this->assertEquals(1, visits($post)->period('day')
            ->timeLeft()->diffInSeconds());

        $this->assertEquals(1,  visits('awssat\Visits\Tests\Post')
            ->period('day')->timeLeft()->diffInSeconds());

        //after seconds it should be empty for week and day
        sleep(1);

        $this->assertEquals([1, 0,], [
            visits($post)->count(),
            visits($post)->period('day')->count(),
        ]);

        //he came after a 5 minute later
        Carbon::setTestNow(Carbon::now()->addMinutes(5));

        sleep(1);

        visits($post)->forceIncrement();

        $this->assertEquals([2, 1,], [
            visits($post)->count(),
            visits($post)->period('day')->count(),
        ]);


        //time until redis delete periods
        $this->assertEquals(1, visits($post)->period('day')->timeLeft()->diffInDays($time));

        //time until redis delete periods
        $this->assertEquals(1, visits('awssat\Visits\Tests\Post')->period('day')->timeLeft()->diffInDays($time));
    }

    /** @test */
    public function all_periods()
    {
        //somone add something on end of the week
        Carbon::setTestNow(Carbon::now()->startOfMonth()->endOfWeek());

        $post = Post::create()->fresh();

        visits($post)->increment();

        //it should be there fo breif of time
        $this->assertEquals([1, 1, 1, 1, 1], [
            visits($post)->count(),
            visits($post)->period('day')->count(),
            visits($post)->period('week')->count(),
            visits($post)->period('month')->count(),
            visits($post)->period('year')->count()
        ]);

        //after seconds it should be empty for week and day
        sleep(1);
        $this->assertEquals([1, 0, 0, 1, 1], [
            visits($post)->count(),
            visits($post)->period('day')->count(),
            visits($post)->period('week')->count(),
            visits($post)->period('month')->count(),
            visits($post)->period('year')->count()
        ]);

        //he came after a 5 minute later
        Carbon::setTestNow(Carbon::now()->endOfWeek()->addHours(1));

        sleep(1);
        visits($post)->forceIncrement();

        $this->assertEquals([2, 1, 1, 2, 2], [
            visits($post)->count(),
            visits($post)->period('day')->count(),
            visits($post)->period('week')->count(),
            visits($post)->period('month')->count(),
            visits($post)->period('year')->count()
        ]);
    }

    /** @test */
    public function total_periods()
    {
        //somone add something on end of the week
        Carbon::setTestNow(Carbon::now()->startOfMonth()->endOfWeek());

        $post = Post::create()->fresh();

        visits($post)->increment();

        $post2 = Post::create()->fresh();

        visits($post2)->increment();

        //it should be there fo breif of time
        $this->assertEquals([2, 2, 2, 2, 2], [
            visits('awssat\Visits\Tests\Post')->count(),
            visits('awssat\Visits\Tests\Post')->period('day')->count(),
            visits('awssat\Visits\Tests\Post')->period('week')->count(),
            visits('awssat\Visits\Tests\Post')->period('month')->count(),
            visits('awssat\Visits\Tests\Post')->period('year')->count()
        ]);

        //after seconds it should be empty for week and day
        sleep(1);
        $this->assertEquals([2, 0, 0, 2, 2], [
            visits('awssat\Visits\Tests\Post')->count(),
            visits('awssat\Visits\Tests\Post')->period('day')->count(),
            visits('awssat\Visits\Tests\Post')->period('week')->count(),
            visits('awssat\Visits\Tests\Post')->period('month')->count(),
            visits('awssat\Visits\Tests\Post')->period('year')->count()
        ]);

        //he came after a 5 minute later
        Carbon::setTestNow(Carbon::now()->endOfWeek()->addHours(1));

        sleep(1);
        visits($post2)->forceIncrement();
        visits($post2)->forceIncrement();

        $this->assertEquals([4, 2, 2, 4, 4], [
            visits('awssat\Visits\Tests\Post')->count(),
            visits('awssat\Visits\Tests\Post')->period('day')->count(),
            visits('awssat\Visits\Tests\Post')->period('week')->count(),
            visits('awssat\Visits\Tests\Post')->period('month')->count(),
            visits('awssat\Visits\Tests\Post')->period('year')->count()
        ]);
    }
}
