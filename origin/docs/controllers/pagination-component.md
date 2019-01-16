# Pagination Component

When you are displaying multiple records to users, you will want paginate them, this is done in the background using the `PaginationComponent` and the `PaginationHelper`.

From the controller action that you want use pagination, call the controller method `paginate` this will load the component and helper, and paginate the records for you.

`paginate(string $model = null, array $settings = [])`

````php 

    public $paginate = [

    ];

    function index(){
         $this->set('bookmarks', $this->paginate('Bookmark'));
    }

````

By default it will look at the default pagination settings in the controller paginate attribute. But you can also pass settings through the paginate method.

You can pass an array with the following keys, which are the same as used in Models.

- **fields** is an array of fields that you want to return in the query
- **order** is either a string or an array of how you want the data to be ordered.
- **group** is for the database group query results.
- **limit** this sets how many rows are returned.
- **callbacks** If this is set to true, before or after then it will call the model callbacks.
- **recursive** depending upon levels of recursion that you want to go.
- **joins**  An array of join settings to join a table.

```php
    $settings = [
         'joins' =  [
            [
                'table' => 'authors',
                'alias' => 'Author',
                'type' => 'LEFT', 
                'conditions' => [ 'Author.id = Article.author_id']
            ]
    ];
```
