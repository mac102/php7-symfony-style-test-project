<?php
namespace App;

use App\Format\JSON;
use App\Format\XML;
use App\Format\FormatInterface;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use App\Annotations\Route;

class Kernel 
{
    private $container;
    private $routes;

    public function __construct()
    {
        $this->container = new Container();
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function boot()
    {
        $this->bootContainer($this->container);
        return $this;
    }

    public function bootContainer(Container $container)
    {
        $container->addService('format.json', function() use ($container) {
            return new JSON();
        });

        $container->addService('format.xml', function() use ($container) {
            return new XML();
        });

        $container->addService('format', function() use ($container) {
            return $container->getService('format.xml');
        }, FormatInterface::class);

        $container->loadServices('App\\Service');

        AnnotationRegistry::registerLoader('class_exists');
        $reader = new AnnotationReader();

        $routes = [];

        $container->loadServices(
            'App\\Controller',
            function(string $serviceName, \ReflectionClass $class) use ($reader, &$routes) {
                $route = $reader->getClassAnnotations($class, Route::class);
                
                if (!$route) {
                    return;
                }
                
                $baseRoute = $route[0]->route;
                
                foreach($class->getMethods() as $method) {
                    $route = $reader->getMethodAnnotation($method, Route::class);

                    if (!$route) {
                        continue;
                    }
                    
                    $this->routes[str_replace('//', '/', $baseRoute . $route->route)] = [
                        'service' => $serviceName,
                        'method' => $method->getName()
                    ];
                }
            }
        );
    }

    public function handleRequest()
    {
        $uri = $this->removeProjectPath($_SERVER['REQUEST_URI']);
        if (isset($this->routes[$uri])) {
            $route = $this->routes[$uri];
            $response = $this->container->getService($route['service'])
                ->{$route['method']}();
            echo $response;
            die;
        } else {
            echo '<br>nie znaleziono';
        }
    }

    /**
     * Usuwa wszystko z uri gdy adres nie wskazuje bezpo¶rednio na katalog public/
     * np /projekty/proj1/public/
     *
     * @param string $uri
     * @return string
     */
    private function removeProjectPath(string $uri): string
    {
        $pos = strpos($uri,'/index.php');
        if ($pos === false) {
            return $uri;
        }
        
        return substr($uri, $pos+10);
    }
}