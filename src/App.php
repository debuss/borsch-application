<?php
/**
 * @author debuss-a
 */

namespace Borsch\Application;

use Borsch\RequestHandler\Emitter;
use Borsch\Router\FastRouteRouter;
use Borsch\Router\RouterInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
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

    /** @var ContainerInterface */
    protected $container;

    /** @var MiddlewareResolver */
    protected $resolver;

    /**
     * App constructor.
     * @param RequestHandlerInterface $request_handler
     * @param RouterInterface $router
     * @param ContainerInterface $container
     */
    public function __construct(RequestHandlerInterface $request_handler, RouterInterface $router, ContainerInterface $container)
    {
        $this->request_handler = $request_handler;
        $this->router = $router;
        $this->container = $container;

        $this->resolver = new MiddlewareResolver($this->container);
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
        if (isset($arguments[1]) && strlen($arguments[1])) {
            $arguments[1] = $this->resolver->resolve($arguments[1]);
        }

        return $this->router->{$name}(...$arguments);
    }

    /**
     * @param string|array|MiddlewareInterface $middleware_or_path
     * @param string|array|callable|MiddlewareInterface $middleware
     */
    public function pipe($middleware_or_path, $middleware = null): void
    {
        $middleware = $middleware ?: $middleware_or_path;
        $path = $middleware === $middleware_or_path ? '/' : $middleware_or_path;

        $middleware = $path != '/' ?
            new PipePathMiddleware($path, $this->resolver->resolve($middleware)) :
            $this->resolver->resolve($middleware);

        $this->request_handler->middleware($middleware);
    }

    /**
     * @param ServerRequestInterface $server_request
     */
    public function run(ServerRequestInterface $server_request): void
    {
        $response = $this->request_handler->handle($server_request);

        $emitter = new Emitter();
        $emitter->emit($response);
    }
}
