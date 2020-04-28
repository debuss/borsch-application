<?php
/**
 * @author debuss-a
 */

namespace Borsch\Application;

use Borsch\RequestHandler\Emitter;
use Borsch\RequestHandler\RequestHandler;
use Borsch\Router\FastRouteRouter;
use Borsch\Router\RouterInterface;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class App
 * @package Borsch\Application
 * @mixin FastRouteRouter
 */
class App
{

    /** @var RequestHandlerInterface */
    protected $request_handler;

    /** @var RouterInterface */
    protected $router;

    /**
     * App constructor.
     * @param RequestHandlerInterface|null $request_handler
     * @param RouterInterface|null $router
     */
    public function __construct(?RequestHandlerInterface $request_handler = null, ?RouterInterface $router = null)
    {
        $this->request_handler = $request_handler ?: new RequestHandler();
        $this->router = $router ?: new FastRouteRouter();
    }

    /**
     * Used to call the router methods.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->router->{$name}(...$arguments);
    }

    /**
     * @param MiddlewareInterface|string $middleware_or_path
     * @param MiddlewareInterface|null $middleware
     */
    public function pipe($middleware_or_path, ?MiddlewareInterface $middleware = null): void
    {
        $middleware = $middleware ?: $middleware_or_path;
        $path = $middleware === $middleware_or_path ? '/' : $middleware_or_path;

        $middleware = $path != '/' ?
            new PipePathMiddleware($path, $middleware) :
            new $middleware;

        $this->request_handler->middleware($middleware);
    }

    /**
     * @param ServerRequestInterface $server_request
     */
    public function run(ServerRequestInterface $server_request): void
    {
        $server_request = $server_request
            ->withAttribute(
                RouterInterface::class,
                $this->router
            )
            ->withAttribute(
                ResponseFactoryInterface::class,
                new ResponseFactory()
            )
            ->withAttribute(
                StreamFactoryInterface::class,
                new StreamFactory()
            );

        $response = $this->request_handler->handle($server_request);

        $emitter = new Emitter();
        $emitter->emit($response);
    }
}
