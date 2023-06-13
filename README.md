# Laravel 5.x - 10.x Repository-Service Pattern

The repository service pattern in Laravel is a widely used architectural pattern that provides a structured approach to accessing and managing data in an application. It serves as an intermediary layer between the application's business logic and the underlying data storage mechanism, such as a database.

The purpose of using the repository service pattern in Laravel is to decouple the application's business logic from the specific implementation details of data storage. By doing so, it promotes code reusability, maintainability, and testability. The pattern achieves this by defining a set of interfaces or contracts that represent the operations and queries related to data access.

Here are some key purposes and benefits of using the repository service pattern in Laravel:

- Abstraction and encapsulation: The pattern abstracts away the underlying data storage technology, allowing the application to switch between different storage mechanisms (e.g., databases, APIs) without affecting the business logic. This encapsulation ensures that the application is not tightly coupled to a specific data source, increasing its flexibility.
- Separation of concerns: The repository service pattern separates the responsibilities of data access and manipulation from the rest of the application's code. This separation enhances the maintainability of the codebase by clearly defining the boundaries and responsibilities of each component.

- Testability: By using repositories as an abstraction layer, it becomes easier to write unit tests for the application's business logic. Mock implementations can be used during testing, allowing the business logic to be tested independently of the actual data storage.

- Code organization and reusability: The pattern promotes a structured approach to organizing code related to data access. It provides a clear and consistent API for data operations, making it easier for developers to understand and work with the data layer. Additionally, repositories can be reused across different parts of the application, avoiding code duplication.

- Caching and performance optimizations: With the repository service pattern, you can implement caching strategies at the repository level. This allows you to optimize performance by caching frequently accessed data, reducing the number of queries made to the underlying data storage.

Overall, the repository service pattern in Laravel provides a structured and flexible approach to managing data access in applications, contributing to better code organization, maintainability, and testability.

Supports soft delete functionality (via trait or custom column).

Supports composite model keys.

## Installation

### Composer

Execute the following command to get the latest version of the package:

```terminal
composer require adobrovolsky97/laravel-repository-service-pattern
```

## Methods

### Adobrovolsky97\LaravelRepositoryServicePattern\Repositories\Contracts\BaseRepositoryInterface

- `with(array $with): self` - define eager loading relations to append to the query
- `withCount(array $withCount): self` - define eager loading relations count to append to the query
- `withTrashed(): self` - add trashed models to the query result (if model supports Soft Delete)
- `onlyTrashed(): self` - return only trashed models as a query result (if model supports Soft Delete)
- `withoutTrashed(): self` - remove trashed models from the query result (if model supports Soft Delete)
- `find(mixed $key): ?Model` - find model by primary key
- `findOrFail(mixed $key, ?string $column = null): ?Model` - find or fail model by PK or another field
- `findFirst(array $attributes): ?Model` - find first model by given attributes, e.g `[['email', 'test@email.com'], ['anotherProperty', '>=', 'val']]`
- `findMany(array $attributes): ?Model` - find many models by given attributes, e.g `[['email', 'test@email.com'], ['anotherProperty', '>=', 'val']]`
- `getAll(array $search = []): Collection` - get collection of models (and apply filters)
- `getAllCursor(array $search = []): LazyCollection` - get collection of models as cursor (and apply filters)
- `getAllPaginated(array $search = [], int $pageSize): LengthAwarePaginator` - get collection of models with pagination (and apply filters), pageSize can be changed dynamically by passing query param `page_size`
- `count(array $search = []): int` - get count of models which fit search criteria
- `create(array $data): ?Model` - create model entity
- `insert(array $data): bool` - bulk data insert
- `update(mixed $keyOrModel, array $data): Model` - update model entity
- `updateOrCreate(array $attributes, array $data): ?Model` - update or create model if not exists
- `delete(mixed $keyOrModel): bool` - delete model (or forceDelete if model supports Soft Delete)
- `softDelete(mixed $keyOrModel): void` - soft delete model (if model supports Soft Delete)
- `restore(mixed $keyOrModel): void` - restore model (if model supports Soft Delete)

### Adobrovolsky97\LaravelRepositoryServicePattern\Repositories\Contracts\BaseCachableRepositoryInterface
This one supports the same methods, the only difference that it supports caching models & collections

### Adobrovolsky97\LaravelRepositoryServicePattern\Services\Contracts\BaseCrudServiceInterface

- `with(array $with): self` - define eager loading relations to append to the query
- `withCount(array $withCount): self` - define eager loading relations count to append to the query
- `withTrashed(): self` - add trashed models to the query result (if model supports Soft Delete)
- `onlyTrashed(): self` - return only trashed models as a query result (if model supports Soft Delete)
- `withoutTrashed(): self` - remove trashed models from the query result (if model supports Soft Delete)
- `getAll(array $search = []): Collection` - get collection of models (and apply filters)
- `getAllCursor(array $search = []): LazyCollection` - get collection of models as cursor (and apply filters)
- `getAllPaginated(array $search = [], int $pageSize): LengthAwarePaginator` - get collection of models with pagination (and apply filters), pageSize can be changed dynamically by passing query param `page_size`
- `count(array $search = []): int` - get count of models which fit search criteria
- `find(mixed $key): ?Model` - find model by primary key
- `findOrFail(mixed $key, ?string $column = null): ?Model` - find or fail model by PK or another field
- `create(array $data): ?Model` - create model entity
- `createMany(array $data): Collection` - create many models
- `insert(array $data): bool` - bulk data insert
- `update(mixed $keyOrModel, array $data): Model` - update model entity
- `updateOrCreate(array $attributes, array $data): ?Model` - update or create model if not exists
- `delete(mixed $keyOrModel): bool` - delete model (or forceDelete if model supports Soft Delete)
- `deleteMany(array $keysOrModels): void` - delete models (or forceDelete if model supports Soft Delete)
- `softDelete(mixed $keyOrModel): void` - soft delete model (if model supports Soft Delete)
- `restore(mixed $keyOrModel): void` - restore model (if model supports Soft Delete)

## Usage

### Create a Model

Create your model e.g `Post`

```php
namespace App;

class Post extends Model {

    protected $fillable = [
        'title',
        'author',
        ...
     ];

     ...
}
```

### Create Repository

```php
namespace App;

use Adobrovolsky97\LaravelRepositoryServicePattern\Repositories\BaseRepository;

class PostRepository extends BaseRepository implements PostRepositoryInterface {

    /**
     * Specify Model class name
     *
     * @return string
     */
    protected function getModelClass(): string
    {
        return Post::class;
    }
}
```

### Create Service

```php
namespace App;

use Adobrovolsky97\LaravelRepositoryServicePattern\Repositories\BaseRepository;

class PostService extends BaseCrudService implements PostServiceInerface {

    /**
     * Specify Repository class name
     *
     * @return string
     */
    protected function getRepositoryClass(): string
    {
        return PostRepositoryInteface::class;
    }
}
```

### Link Service to its contract in ServiceProvider

```php
class AppServiceProvider extends ServiceProvider {

    /**
     * Specify Repository class name
     *
     * @return string
     */
    public function register(): void
    {
        $this->app->singleton(PostRepositoryInterface::class, PostRepository::class);
        $this->app->singleton(PostServiceInterface::class, PostService::class);
    }
}
```

Now the Service is ready for work.

### Use methods

```php
namespace App\Http\Controllers;

use App\PostServiceInterface;

class PostsController extends Controller {

    /**
     * @var PostServiceInterface
     */
    protected PostServiceInterface $service;

    public function __construct(PostServiceInterface $service) 
    {
        $this->service = $service;
    }
    ....
}
```
CRUD Controller Actions Example 


Index 

```php
public function index(SearchRequest $request): AnonymousResourceCollection
{
    return PostResource::collection($this->service->withTrashed()->getAllPaginated($request->validated(), 25));
}
```

Show

```php
public function show(int $postId): PostResource
{
    return PostResource::make($this->service->findOrFail($postId));
}
```

Store

```php
public function store(StoreRequest $request): PostResource
{
    return PostResource::make($this->service->create($request->validated()));
}
```

Update

```php
public function update(Post $post, UpdateRequest $request): PostResource
{
    return PostResource::make($this->service->update($post, $request->validated()));
}
```

Destroy

```php
public function destroy(Post $post): JsonResponse
{
    $this->service->delete($post);
    // Or  
    $this->service->softDelete($post); 
       
    return Response::json(null, 204);
}
```

Restore

```php
public function restore(Post $deletedPost): PostResource
{
    $this->service->restore($deletedPost);
       
    return PostResource::make($deletedPost->refresh());
}
```

### Soft Deletes

You need to add at least soft delete column (`deleted_at`) to the table to start using soft deletes from the service.

Also, it is possible to use it together with `SoftDeletes` trait

By default soft delete column name is `deleted_at`, you may override it by defining variable inside your repository

`protected $deletedAtColumnName = 'custom_deleted_at';`

By default, soft deleted records excluded from the query result data 
```php
$posts = $this->service->getAll();
// Those are equivalent
$posts = $this->service->withoutTrashed()->getAll();
```

Showing only soft deleted records

```php
$posts = $this->service->onlyTrashed()->getAll();
```

Showing only NOT soft deleted records

```php
$posts = $this->service->withoutTrashed()->getAll();
```

### Loading the Model relationships

```php
$post = $this->service->with(['author'])->withCount(['readers'])->getAll();
```

### Query results filtering

By default filtering will be handled by `applyFilterConditions()`, but you may probably need to do custom filtering, so override `applyFilters` method in your repository if you need custom filtering

```php
class PostRepository extends BaseRepository implements PostRepositoryInterface {
   
   /**
    * Override this method in your repository if you need custom filtering
    * 
    * @param array $searchParams
    * @return Builder
    */
    protected function applyFilters(array $searchParams = []): Builder 
    {
        return $this
            ->getQuery()
            ->when(isset($searchParams['title']), function (Builder $query) use ($searchParams) {
                $query->where('title', 'like', "%{$searchParams['title']}%");
            })
            ->orderBy('id');
    }
}
```

Find many models by multiple fields

```php
$posts = $this->repository->findMany([
    'field' => 'val' // where('field', '=', 'val')
    ['field', 'val'], // where('field', '=', 'val')
    ['field' => 'val'], // where('field', '=', 'val')
    ['field', '=', 'val'], // where('field', '=', 'val')
    ['field', '>', 'val'], // where('field', '>', 'val')
    ['field', 'like', '%val%'], // where('field', 'like', '%val%')
    ['field', 'in', [1,2,3]], // whereIn('field', [1,2,3])
    ['field', 'not_in', [1,2,3]], // whereNotIn('field', [1,2,3])
    ['field', 'null'], // whereNull($field)
    ['field', 'not_null'], // whereNotNull($field)
    ['field', 'date', '2022-01-01'], // whereDate($field, '=', '2022-01-01')
    ['field', 'date <=', '2022-01-01'], // whereDate($field, '<=', '2022-01-01')
    ['field', 'date >=', '2022-01-01'], // whereDate($field, '>=', '2022-01-01')
    ['field', 'day >=', '01'], // whereDay($field, '>=', '01')
    ['field', 'day', '01'], // whereDay($field, '=', '01')
    ['field', 'month', '01'], // whereMonth($field, '=', '01')
    ['field', 'month <', '01'], // whereMonth($field, '<', '01')
    ['field', 'year <', '2022'], // whereYear($field, '<', '2022')
    ['field', 'year', '2022'], // whereYear($field, '=', '2022')
    ['relation', 'has', function($query) {// your query}], // whereHas('relation', function($query) { // your query}})
    ['relation', 'DOESNT_HAVE', function($query) {// your query}], // whereDoesntHave('relation', function($query) { // your query}})
    ['relation', 'HAS_MORPH', function($query) {// your query}], // whereHasMorph('relation', function($query) { // your query}})
    ['relation', 'DOESNT_HAVE_MORPH', function($query) {// your query}], // whereDoesntHaveMorph('relation', function($query) { // your query}})
    ['field', 'between', [1,5]], // whereBetween('field', [1,5])
    ['field', 'NOT_BETWEEN', [1,5]], // whereNotBetween('field', [1,5])
]);
```

### Caching

If you want to apply caching to your models - extend your entity repository with the 

### Code Generator


#### Allows generating Classes/Interfaces/Traits

`$template = new ClassTemplate();`

`$template->setType('class')->setName('ClassName')->setNamespace('Path\\To\\Class');`

It is possible define properties, methods, constants, extends, implements, doc block comments, create abstract/final
classes, set method body, etc...

#### Eloquent Model Generation Feature

Command `php artisan generate:model {table}` - generates the model for the table, define all relations,
properties, define doc block with props annotations. (will not override model if the one already exists)

#### Repository-Service pattern files generation

Command `php artisan generate:repository-service {table}` generates repository and service for a model (will not override model if the one already exists)

#### Requests generation

Command `php artisan generate:request {tableAndNamespace} {modelNamespace?}` will generate a Request instance,

- `{tableAndNamespace}` will specify the folder of the new request (e.g. `User\\StoreRequest` will create
  a `App\\Http\\Requests\\User\\StoreReqeust`);
- `{modelNamespace?}` - is optional param. e.g. `App\\Models\\ModelName` will generate request with rules for
  model `fillable` attributes;

#### Resource generation

Command `php artisan generate:resource {table}` generates resource for a particular Model by table name (will not override model if the one already exists)

#### Api Resource Controller generation

Command `php artisan generate:resource-controller {table}` will generate a resource controller for a particular Model
entity by table name (will not override model if the one already exists)

The controller actions will be based on the Repository-Service laravel pattern package;

#### CRUD generation

Command `php artisan generate:crud {table}` will generate all entities mentioned above (will not override model if the one already exists) 

- Model (if not exists)
- Repository Interface (if not exists)
- Repository (if not exists)
- Service Interface (if not exists)
- Service (if not exists)
- Resource (if not exists)
- StoreRequest and UpdateRequest (if not exists)
- Api Resource Controller (if not exists)

