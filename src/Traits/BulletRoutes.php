<?php

namespace MarcoT89\Bullet\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use MarcoT89\Bullet\Controllers\BaseController;

trait BulletRoutes
{
    protected $ignoreClasses = [
        BaseController::class,
        \Illuminate\Routing\Controller::class,
        'App\\Http\\Controllers\\Controller',
    ];
    protected $namespace;
    protected $httpMethods;
    protected $controllerInstances = [];

    public function namespace(string $namespace = null, array $options = [])
    {
        $this->namespace           = $namespace ?? '';
        $this->controllerInstances = [];

        if (isset($options['except'])) {
            collect($options['except'])->each(function ($controller) {
                $this->ignoreClasses[] = $this->getNamespaced($controller);
            });
        }

        $controllers = $this->mapMethods($this->getControllers());

        $this->makeRoutes($controllers);
    }

    private function getControllers(): Collection
    {
        $dirs  = new \IteratorIterator(new \DirectoryIterator(app_path('Http/Controllers/' . $this->namespace)));
        $files = collect();
        foreach ($dirs as $file) {
            if ($file->isDir() || $file->getBasename() === 'Controller.php') {
                continue;
            }
            $files->push(str_replace('.php', '', $file->getBasename()));
        }

        return $files->filter(function ($controller) {
            return class_exists($this->getNamespaced($controller));
        });
    }

    private function getNamespaced($controller)
    {
        $namespace = str_replace('/', '\\', $this->namespace);

        return str_replace('\\\\', '\\', Str::studly('App\\Http\\Controllers\\' . $namespace . '\\' . $controller));
    }

    private function getNamespacedForRoute($controller)
    {
        $namespaced = $this->getNamespaced($controller);

        return str_replace('App\\Http\\Controllers\\', '', $namespaced);
    }

    private function mapMethods(Collection $controllers): Collection
    {
        return $controllers->mapWithKeys(function ($controller) {
            $class = new \ReflectionClass($this->getNamespaced($controller));
            $methods = collect($class->getMethods())->filter(function ($method) {
                return $method->isPublic()
                    && !Str::startsWith($method->name, '__')
                    && !collect($this->ignoreClasses)->contains($method->class);
            })->map->name->values()->toArray();

            return [$controller => $methods];
        });
    }

    private function makeRoutes(Collection $controllers)
    {
        foreach ($controllers as $controllerName => $actions) {
            $controller = $this->getNamespacedForRoute($controllerName);
            foreach ($actions as $action) {
                $httpMethod = $this->inferHttpMethodFromActionName($action);
                $model      = class_basename($this->getModelFromController($controllerName));
                $url        = $this->getRouteOf($controllerName, $model, $action);
                $route      = $this->getRouteNameFrom($controllerName);
                $routeName  = Str::kebab($this->sanitizeMethodName($action));

                if (!$this->shouldDisplayRoute($controllerName, $action)) {
                    continue;
                }

                Route::{$httpMethod}("$url", "$controller@$action")->name("$route.$routeName");
            }
        }
    }

    private function shouldDisplayRoute($controller, $action)
    {
        $only                 = collect($this->getControllerPropValue($controller, 'only'));
        $except               = collect($this->getControllerPropValue($controller, 'except'));
        $implementsSoftDelete = false;
        $softDeleteActions    = collect(['forceDelete', 'restore']);

        if ($softDeleteActions->contains($action)) {
            $model                = $this->getModelFromController($controller);
            $modelObj             = new $model();
            $implementsSoftDelete = method_exists($modelObj, 'initializeSoftDeletes');

            return $implementsSoftDelete && $only->isEmpty() && $except->isEmpty()
                || ($implementsSoftDelete && $only->isNotEmpty() && $only->contains($action))
                || ($implementsSoftDelete && $only->isEmpty() && !$except->contains($action));
        }

        if ($only->isNotEmpty()) {
            return $only->contains($action);
        }

        if ($except->isNotEmpty()) {
            return !$except->contains($action);
        }

        return true;
    }

    private function getRouteOf(string $controller, string $model, string $action)
    {
        $defaultRoute          = $this->getRouteNameFrom($controller);
        $modelSlug             = Str::kebab($model);
        $modelInVariableFormat = Str::camel($modelSlug);
        $methodSlug            = Str::kebab($this->sanitizeMethodName($action));
        $urlParams             = $this->getMethodParametersOf($controller, $action)
            ->map(function (\ReflectionParameter $param) {
                return '{' . $param->getName() . '}';
            })->implode('/');

        switch ($action) {
            case 'index':
                return $defaultRoute;
            case 'update':
            case 'show':
            case 'destroy':
                return "$defaultRoute/{" . $modelInVariableFormat . '}';
            case 'forceDelete':
                return "$defaultRoute/{" . $modelInVariableFormat . '}/force-delete';
            case 'restore':
                return "$defaultRoute/{" . $modelInVariableFormat . '}/restore';
            case 'edit':
                return "$defaultRoute/{" . $modelInVariableFormat . '}/edit';
            case 'store':
                return "$defaultRoute";
            default:
                return "$defaultRoute/$methodSlug/$urlParams";
        }
    }

    private function getRouteNameFrom(string $controller)
    {
        $names = explode('_', Str::snake($controller));
        array_pop($names);

        return Str::plural(implode('-', $names));
    }

    private function getMethodParametersOf(string $controller, string $method): Collection
    {
        $controller = $this->getNamespaced($controller);
        $ref        = new \ReflectionClass($controller);

        return collect($ref->getMethod($method)->getParameters())->filter(function (\ReflectionParameter $param) {
            if (!$param->hasType() || $param->getClass() === null) {
                return $param;
            }

            return !$param->getClass()->isSubclassOf('Illuminate\\Http\\Request')
                && $param->getType()->getName() !== 'Illuminate\\Http\\Request';
        })->values();
    }

    private function sanitizeMethodName(string $method): string
    {
        $sanitized = $method;

        $this->httpMethods()->each(function ($httpMethod) use (&$sanitized) {
            if (Str::startsWith($sanitized, $httpMethod) && (strlen($sanitized) !== strlen($httpMethod))) {
                $sanitized = substr($sanitized, strlen($httpMethod));
            }
        });

        return $sanitized;
    }

    private function inferHttpMethodFromActionName(string $method)
    {
        $resourceMethods = collect([
            'index',
            'store',
            'update',
            'show',
            'create',
            'edit',
            'destroy',
            'forceDelete',
            'restore',
        ]);

        if ($resourceMethods->contains($method)) {
            return $this->getResourceHttpMethodFrom($method);
        }

        list($httpMethod) = explode('-', Str::kebab($method));

        return $this->httpMethods()->contains($httpMethod) ? $httpMethod : 'get';
    }

    private function httpMethods(): Collection
    {
        if ($this->httpMethods) {
            return $this->httpMethods;
        }

        return $this->httpMethods = collect(['get', 'post', 'put', 'patch', 'delete']);
    }

    private function getResourceHttpMethodFrom(string $method)
    {
        switch ($method) {
            case 'index':
            case 'show':
            case 'edit':
            case 'create':
                return 'get';
            case 'store':
                return 'post';
            case 'update':
            case 'restore':
                return 'put';
            case 'destroy':
            case 'forceDelete':
                return 'delete';
        }
        throw new \LogicException('There is no http method defined for the resource method "' . $method . '"');
    }

    private function getModelFromController(string $controller)
    {
        $model = $this->getControllerPropValue($controller, 'model');

        // Infer model name from controller
        list($controller) = explode('-', Str::kebab($controller));

        return $model ?? 'App\\Models\\' . Str::studly($controller);
    }

    private function getControllerInstance($controller)
    {
        $controller = class_basename($controller);
        $controller = $this->getNamespaced($controller);

        return $this->controllerInstances[$controller] = $this->controllerInstances[$controller]
            ?? $this->createControllerInstance($controller);
    }

    private function createControllerInstance($controller)
    {
        $controller      = class_basename($controller);
        $controllerClass = $this->getNamespaced($controller);

        $controllerObject = resolve($controllerClass);

        return is_string($controllerObject) ? new $controllerObject : $controllerObject;
    }

    private function getControllerPropValue($controller, $property)
    {
        $object     = $this->getControllerInstance($controller);
        $reflection = new \ReflectionObject($object);

        if (!$reflection->hasProperty($property)) {
            return null;
        }

        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);

        return $prop->getValue($object);
    }
}
