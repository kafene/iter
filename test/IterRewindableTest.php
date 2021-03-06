<?php

namespace iter;

use iter\rewindable;

class IterRewindableTest extends \PHPUnit_Framework_TestCase {
    private function assertRewindableEquals($array, $iter, $withKeys = false) {
        $fn = $withKeys ? 'iter\\toArrayWithKeys' : 'iter\\toArray';
        $this->assertEquals($array, $fn($iter));
        $this->assertEquals($array, $fn($iter));
    }

    public function testRewindableVariants() {
        $this->assertRewindableEquals(
            [1, 2, 3, 4, 5],
            rewindable\range(1, 5)
        );
        $this->assertRewindableEquals(
            [3, 6, 9, 12, 15],
            rewindable\map(fn\operator('*', 3), rewindable\range(1, 5))
        );
        $this->assertRewindableEquals(
            [-5, -4, -3, -2, -1],
            rewindable\filter(fn\operator('<', 0), rewindable\range(-5, 5))
        );
        $this->assertRewindableEquals(
            [[0,5], [1,4], [2,3], [3,2], [4,1], [5,0]],
            rewindable\zip(rewindable\range(0, 5), rewindable\range(5, 0, -1))
        );
        $this->assertRewindableEquals(
            [5=>0, 4=>1, 3=>2, 2=>3, 1=>4, 0=>5],
            rewindable\zipKeyValue(rewindable\range(0, 5), rewindable\range(5, 0, -1)),
            true
        );
        $this->assertRewindableEquals(
            [1, 2, 3, 4, 5, 6, 7, 8, 9],
            rewindable\chain(rewindable\range(1, 3), rewindable\range(4, 6), rewindable\range(7, 9))
        );
        $this->assertRewindableEquals(
            [5, 6, 7, 8, 9],
            rewindable\slice(rewindable\range(0, 9), 5)
        );
        $this->assertRewindableEquals(
            [1, 2, 3],
            rewindable\take(3, [1, 2, 3, 4, 5])
        );
        $this->assertRewindableEquals(
            [4, 5],
            rewindable\drop(3, [1, 2, 3, 4, 5])
        );
        $this->assertRewindableEquals(
            [1, 1, 1, 1, 1],
            rewindable\repeat(1, 5)
        );
        $this->assertRewindableEquals(
            ['b', 'd', 'f'],
            rewindable\values(['a' => 'b', 'c' => 'd', 'e' => 'f']),
            true
        );
        $this->assertRewindableEquals(
            ['a', 'c', 'e'],
            rewindable\keys(['a' => 'b', 'c' => 'd', 'e' => 'f']),
            true
        );
        $this->assertRewindableEquals(
            [3, 1, 4],
            rewindable\takeWhile(fn\operator('>', 0), [3, 1, 4, -1, 5])
        );
        $this->assertRewindableEquals(
            [-1, 5],
            rewindable\dropWhile(fn\operator('>', 0), [3, 1, 4, -1, 5])
        );
        $this->assertRewindableEquals(
            [1, 2, 3, 4, 5],
            rewindable\flatten([[1, [[2, [[]], 3], 4]], 5])
        );
    }

    public function testMakeRewindable() {
        $range = makeRewindable('iter\\range');
        $map = makeRewindable('iter\\map');
        $this->assertRewindableEquals(
            [3, 6, 9, 12, 15],
            $map(fn\operator('*', 3), $range(1, 5))
        );
    }

    public function testCallRewindable() {
        $this->assertRewindableEquals(
            [3, 6, 9, 12, 15],
            callRewindable(
                'iter\\map',
                fn\operator('*', 3), callRewindable('iter\\range', 1, 5)
            )
        );
    }

    public function testRewindableGenerator() {
        // Make sure that send() and throw() work with rewindable generator
        $genFn = makeRewindable(function() {
            $startValue = yield;
            try {
                for (;;) yield $startValue++;
            } catch (\Exception $e) {
                yield 'end';
            }
        });
        $gen = $genFn();

        for ($i = 0; $i < 2; ++$i) {
            $gen->rewind();
            $gen->send(10);
            $this->assertEquals(10, $gen->current());
            $gen->next();
            $this->assertEquals(11, $gen->current());
            $gen->next();
            $this->assertEquals(12, $gen->current());
            $gen->throw(new \Exception);
            $this->assertEquals('end', $gen->current());
        }
    }
}