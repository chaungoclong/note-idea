<?php

declare(strict_types=1);

/**
 * Class MiddlewarePipeline
 *
 * This class implements a simple middleware pipeline.
 * Middlewares are executed in the order they are added,
 * and they wrap around a final handler function.
 */
class MiddlewarePipeline
{
    private array $middlewares = [];
    private $handler;

    /**
     * Constructor to initialize the pipeline with a final handler.
     *
     * @param callable $handler The final handler to be executed after all middlewares.
     */
    public function __construct(callable $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Adds a middleware to the pipeline.
     *
     * @param callable $middleware A middleware function that takes the next function as an argument.
     * @return self Returns the instance for method chaining.
     */
    public function addMiddleware(callable $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Executes the middleware pipeline.
     * Each middleware wraps around the next one, and the final handler is executed at the end.
     */
    public function run(): void
    {
        $middlewareChain = array_reduce(
            array_reverse($this->middlewares),
            static fn ($next, $middleware) => static fn () => $middleware($next),
            $this->handler
        );

        $middlewareChain();
    }
}

// Create a middleware pipeline with a final handler.
$pipeline = new MiddlewarePipeline(static function () {
    echo "Hello World!\n";
});

// Add middleware functions to the pipeline.
$pipeline->addMiddleware(static function ($next) {
    echo "Middleware 1\n";
    $next();
});

$pipeline->addMiddleware(static function ($next) {
    echo "Middleware 2\n";
    $next();
});

// Run the middleware pipeline.
$pipeline->run();
