<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Http\View\Helper;

use Origin\Model\Model;
use Origin\Http\Request;
use Origin\Utility\Date;
use Origin\Http\Response;
use Origin\Http\View\View;
use Origin\Model\ModelRegistry;
use Origin\TestSuite\TestTrait;
use Origin\TestSuite\OriginTestCase;
use Origin\Http\Controller\Controller;
use Origin\Http\View\Helper\FormHelper;

class ViewTestsController extends Controller
{
}

class Post extends Model
{
}
class Widget extends Model
{
    /**
     * @todo Recreate tests using fixtures since this test was written before
     * and this has its flaws since schema is set here manually.
     *
     * @var array
     */
    protected $schema = [
        'columns' => [
            'id' => ['type' => 'integer', 'limit' => 11,'key' => 'primary'],
            'name' => ['type' => 'string', 'limit' => 80],
            'description' => ['type' => 'text'],
            'active' => ['type' => 'boolean', 'limit' => 1],
        ],
    ];

    public function initialize(array $config) : void
    {
        $this->validate('name', ['rule' => 'notBlank']);
    }

    public function setSchema($schema)
    {
        $this->schema = $schema;
    }
}

class MockFormHelper extends FormHelper
{
    use TestTrait;
    public function setMeta($meta)
    {
        $this->meta = $meta;
    }
}

class FormHelperTest extends OriginTestCase
{
    protected $fixtures = ['Origin.Post'];
    
    protected function setUp(): void
    {
        $request = new Request('widgets/edit');
        $controller = new ViewTestsController($request, new Response());
        $View = new View($controller);
        $this->Form = new MockFormHelper($View);
        $this->Form->initialize([]);
    }

    public function testCreate()
    {
        $FormHelper = $this->Form;

        $result = $FormHelper->create();
        $expected = '<form method="post" accept-charset="utf-8" action="/widgets/edit">';
        $this->assertEquals($expected, $result);

        $Widget = new Widget();
        $widget = $Widget->new();

        $result = $FormHelper->create($widget);
        $expected = '<form method="post" accept-charset="utf-8" action="/widgets/edit">';
        $this->assertEquals($expected, $result);

        $result = $FormHelper->create($widget, ['type' => 'get']);
        $expected = '<form method="get" accept-charset="utf-8" action="/widgets/edit">';
        $this->assertEquals($expected, $result);

        $result = $FormHelper->create($widget, ['type' => 'file']);
        $expected = '<form enctype="multipart/form-data" method="post" action="/widgets/edit">';
        $this->assertEquals($expected, $result);

        $result = $FormHelper->create($widget, ['class' => 'my-class','id' => 'my-id']);
        $expected = '<form method="post" accept-charset="utf-8" action="/widgets/edit" class="my-class" id="my-id">';
        $this->assertEquals($expected, $result);

        // Create the entity manually from string and validate.
        ModelRegistry::set('Post', new Post());
        $FormHelper->request()->data(['title' => 'Article Title']);
        $this->assertNotNull($FormHelper->create('Article'));
        $this->assertEquals('Article Title', $FormHelper->getProperty('data')->title);
    }

    public function testText()
    {
        $FormHelper = $this->Form;

        $expected = '<input type="text" name="article">';
        $this->assertEquals($expected, $FormHelper->text('article'));

        $expected = '<input type="text" name="article" class="form-control">';
        $this->assertEquals($expected, $FormHelper->text('article', ['class' => 'form-control']));

        $expected = '<input type="text" name="article" class="form-control" disabled>';
        $this->assertEquals($expected, $FormHelper->text('article', ['class' => 'form-control', 'disabled' => true]));

        $expected = '<input type="text" name="article[title]">';
        $this->assertEquals($expected, $FormHelper->text('article.title'));

        $expected = '<input type="text" name="article[0][title]">';
        $this->assertEquals($expected, $FormHelper->text('article.0.title'));
    }

    /**
     * @depends testText
     */
    public function testFormValues()
    {
        $FormHelper = $this->Form;

        $Widget = new Widget();
        $widget = $Widget->new();
        $widget->name = 'foo';

        $widget2 = $Widget->new();
        $widget2->name = 'bar';
        $widget->related = $widget2;

        $widget3 = $Widget->new();
        $widget3->name = 'foo/bar';
        $widget->widgets = [$widget3];

        $result = $FormHelper->create($widget);
        $expected = '<input type="text" name="name" value="foo">';
        $this->assertEquals($expected, $FormHelper->text('name'));

        $expected = '<input type="text" name="related[name]" value="bar">';
        $this->assertEquals($expected, $FormHelper->text('related.name'));

        $expected = '<input type="text" name="widgets[0][name]" value="foo/bar">';
        $this->assertEquals($expected, $FormHelper->text('widgets.0.name'));
    }

    /**
     * @depends testText
     */
    public function testFormValidationErrors()
    {
        $FormHelper = $this->Form;

        $Widget = new Widget();
        $widget = $Widget->new();
        $widget->name = 'foo';
        $widget->invalidate('name', 'its not bar');

        $widget2 = $Widget->new();
        $widget2->name = 'bar';
        $widget2->invalidate('name', 'its not foo');
        $widget->related = $widget2;

        $widget3 = $Widget->new();
        $widget3->name = 'foo/bar';
        $widget3->invalidate('name', 'its messy');

        $widget->widgets = [$widget3];

        $result = $FormHelper->create($widget);
        $expected = '<input type="text" name="name" value="foo" class="error">';
        $this->assertEquals($expected, $FormHelper->text('name'));

        $expected = '<input type="text" name="related[name]" value="bar" class="error">';
        $this->assertEquals($expected, $FormHelper->text('related.name'));

        $expected = '<input type="text" name="widgets[0][name]" value="foo/bar" class="error">';
        $this->assertEquals($expected, $FormHelper->text('widgets.0.name'));
    }

    public function testPassword()
    {
        $FormHelper = $this->Form;

        $expected = '<input type="password" name="password">';
        $this->assertEquals($expected, $FormHelper->password('password'));
    }

    public function testHidden()
    {
        $FormHelper = $this->Form;

        $expected = '<input type="hidden" name="id">';
        $this->assertEquals($expected, $FormHelper->hidden('id'));
    }

    public function testTextarea()
    {
        $FormHelper = $this->Form;

        $expected = '<textarea name="description"></textarea>';
        $this->assertEquals($expected, $FormHelper->textarea('description'));

        $expected = '<textarea name="description">some text here</textarea>';
        $this->assertEquals($expected, $FormHelper->textarea('description', ['value' => 'some text here']));

        $Widget = new Widget();
        $widget = $Widget->new();
        $widget->description = 'a description that sells';

        $result = $FormHelper->create($widget);
        $expected = '<textarea name="description">a description that sells</textarea>';
        $this->assertEquals($expected, $FormHelper->textarea('description'));
    }

    public function testSelect()
    {
        $FormHelper = $this->Form;

        $expected = '<select name="status"><option value="0">draft</option><option value="1">new</option><option value="2">published</option></select>';
        $this->assertEquals($expected, $FormHelper->select('status', ['draft', 'new', 'published']));

        $expected = '<select name="status"><option value="0">draft</option><option value="1">new</option><option value="2">published</option></select>';
        $this->assertEquals($expected, $FormHelper->select('status', ['draft', 'new', 'published'], ['empty' => false]));

        $expected = '<select name="status"><option value="">--None--</option><option value="0">draft</option><option value="1">new</option><option value="2">published</option></select>';
        $this->assertEquals($expected, $FormHelper->select('status', ['draft', 'new', 'published'], ['empty' => true]));

        $expected = '<select name="status"><option value="draft">Draft</option><option value="new">New</option><option value="published">Published</option></select>';
        $this->assertEquals($expected, $FormHelper->select('status', ['draft' => 'Draft', 'new' => 'New', 'published' => 'Published']));

        $groupData = [
            'Group 1' => [
                'Value 1' => 'Text 1',
                'Value 2' => 'Text 2',
            ],
            'Group 2' => [
                'Value 3' => 'Text 3',
            ],
        ];
        $expected = '<select name="status"><optgroup label="Group 1"><option value="Value 1">Text 1</option><option value="Value 2">Text 2</option></optgroup><optgroup label="Group 2"><option value="Value 3">Text 3</option></optgroup></select>';
        $this->assertEquals($expected, $FormHelper->select('status', $groupData));

        $expected = '<select name="status"><option value="0">draft</option><option value="1">new</option><option value="2" selected>published</option></select>';
        $this->assertEquals($expected, $FormHelper->select('status', ['draft', 'new', 'published'], ['value' => 2]));

        $expected = '<select name="status"><option value="0" selected>draft</option><option value="1">new</option><option value="2">published</option></select>';
        $this->assertEquals($expected, $FormHelper->select('status', ['draft', 'new', 'published'], ['value' => 0]));

        $expected = '<select name="status"><option value="">--None--</option><option value="0">draft</option><option value="1">new</option><option value="2">published</option></select>';
        $this->assertEquals($expected, $FormHelper->select('status', ['draft', 'new', 'published'], ['empty' => true, 'value' => null]));

        $Widget = new Widget();
        $widget = $Widget->new();
        $widget->status = 'new';

        $result = $FormHelper->create($widget);
        $expected = '<select name="status"><option value="draft">Draft</option><option value="new" selected>New</option><option value="published">Published</option></select>';
        $this->assertEquals($expected, $FormHelper->select('status', ['draft' => 'Draft', 'new' => 'New', 'published' => 'Published']));
    }

    public function testDate()
    {
        $now = date('Y-m-d H:i:s');
        Date::locale(['date' => 'd/m/Y','time' => 'H:i','datetime' => 'd/m/Y H:i','timezone' => 'Europe/London']);
        $FormHelper = $this->Form;
        $expected = '<input type="text" name="date" placeholder="e.g. ' .Date::formatDate($now) . '">';
        $this->assertEquals($expected, $FormHelper->date('date'));

        $expected = '<input type="text" name="date" value="' .Date::formatDate($now) . '" placeholder="e.g. ' .Date::formatDate($now) . '">';
        $this->assertEquals($expected, $FormHelper->date('date', ['value' => $now]));
    }

    public function testTime()
    {
        $now = date('Y-m-d H:i:s');
        $FormHelper = $this->Form;
        $expected = '<input type="text" name="time" placeholder="e.g. ' .Date::formatTime($now) . '">';
        $this->assertEquals($expected, $FormHelper->time('time'));

        $expected = '<input type="text" name="time" value="' .Date::formatTime($now) . '" placeholder="e.g. ' .Date::formatTime($now) . '">';
        $this->assertEquals($expected, $FormHelper->time('time', ['value' => $now]));
    }

    public function testDatetime()
    {
        $now = date('Y-m-d H:i:s');
        $FormHelper = $this->Form;

        $expected = '<input type="text" name="datetime" placeholder="e.g. ' .Date::formatDateTime($now) . '">';
        $this->assertEquals($expected, $FormHelper->datetime('datetime'));

        $expected = '<input type="text" name="datetime" value="' .Date::formatDateTime($now) . '" placeholder="e.g. ' .Date::formatDateTime($now) . '">';
        $this->assertEquals($expected, $FormHelper->datetime('datetime', ['value' => $now]));
    }

    public function testFile()
    {
        $FormHelper = $this->Form;

        $expected = '<input type="file" name="submittedfile">';
        $this->assertEquals($expected, $FormHelper->file('submittedfile'));
    }

    public function testLabel()
    {
        $FormHelper = $this->Form;

        $expected = '<label for="name">name</label>';
        $this->assertEquals($expected, $FormHelper->label('name'));

        $expected = '<label for="name">Custom</label>';
        $this->assertEquals($expected, $FormHelper->label('name', 'Custom'));

        $Widget = new Widget();
        $widget = $Widget->new();
        $widget->name = 'Widget Name';

        $result = $FormHelper->create($widget);
        $expected = '<label for="name">name</label>';
        $this->assertEquals($expected, $FormHelper->label('name'));
    }

    public function testButton()
    {
        $FormHelper = $this->Form;

        $expected = '<button type="button">save</button>';
        $this->assertEquals($expected, $FormHelper->button('save'));

        $expected = '<button type="submit">save</button>';
        $this->assertEquals($expected, $FormHelper->button('save', ['type' => 'submit']));
    }

    public function testSubmit()
    {
        $FormHelper = $this->Form;

        $expected = '<button type="submit">save</button>';
        $this->assertEquals($expected, $FormHelper->submit('save'));
    }

    public function testCheckbox()
    {
        $FormHelper = $this->Form;

        $expected = '<input type="hidden" name="agree" value="0"><input type="checkbox" name="agree" value="1">';
        $this->assertEquals($expected, $FormHelper->checkbox('agree'));

        $expected = '<input type="checkbox" name="agree" value="1">';
        $this->assertEquals($expected, $FormHelper->checkbox('agree', ['hiddenField' => false]));

        # Test values
        $Widget = new Widget();
        $widget = $Widget->new();
        $widget->active = true;
        $widget->in_stock = false;
        $result = $FormHelper->create($widget);

        $expected = '<input type="checkbox" name="active" value="1" checked>';
        $this->assertEquals($expected, $FormHelper->checkbox('active', ['hiddenField' => false]));
        
        $expected = '<input type="checkbox" name="in_stock" value="1">';
        $this->assertEquals($expected, $FormHelper->checkbox('in_stock', ['hiddenField' => false]));
    }

    public function testControlRadio()
    {
        $FormHelper = $this->Form;
        $result = $FormHelper->control('plan', ['type' => 'radio','options' => [1000 => 'standard',10001 => 'premium']]);
        $expected = '<div class="form-check radio"><input type="radio" name="plan" value="1000" class="form-check-input" id="plan-1000"><label for="plan-1000">standard</label></div><div class="form-check radio"><input type="radio" name="plan" value="10001" class="form-check-input" id="plan-10001"><label for="plan-10001">premium</label></div>';
        $this->assertEquals($expected, $result);
        $result = $FormHelper->control('plan', ['type' => 'radio','options' => [1000 => 'standard',10001 => 'premium'],'value' => 10001]);
        $expected = '<div class="form-check radio"><input type="radio" name="plan" value="1000" class="form-check-input" id="plan-1000"><label for="plan-1000">standard</label></div><div class="form-check radio"><input type="radio" name="plan" value="10001" class="form-check-input" id="plan-10001" checked><label for="plan-10001">premium</label></div>';
        $this->assertEquals($expected, $result);
    }

    public function testRadio()
    {
        $FormHelper = $this->Form;

        $expected = '<input type="radio" name="duplicates" value="0" id="duplicates-0"><label for="duplicates-0">Create New</label><input type="radio" name="duplicates" value="1" id="duplicates-1"><label for="duplicates-1">Overwrite</label><input type="radio" name="duplicates" value="2" id="duplicates-2"><label for="duplicates-2">Delete</label>';
        $this->assertEquals($expected, $FormHelper->radio('duplicates', ['Create New', 'Overwrite', 'Delete']));

        $result = $this->Form->radio('package', [123 => 'Premium',456 => 'Basic'], ['value' => 123]);
        $expected = '<input type="radio" name="package" value="123" id="package-123" checked><label for="package-123">Premium</label><input type="radio" name="package" value="456" id="package-456"><label for="package-456">Basic</label>';
        $this->assertSame($expected, $result);
    }

    public function testPostLink()
    {
        $FormHelper = $this->Form;
        $expected = '<form name="link_123456789" style="display:none" method="post" action="/articles/delete/123"><input type="hidden" name="_method" value="POST"></form><a href="#" onclick="document.link_123456789.submit();">delete</a>';
        $result = $this->Form->postLink('delete', '/articles/delete/123');

        $result = preg_replace('/link_[a-zA-Z0-9]+/', 'link_123456789', $result);
        $this->assertEquals($expected, $result);

        $newResult = $this->Form->postLink('delete', ['controller' => 'Articles','action' => 'delete',123]);
        $newResult = preg_replace('/link_[a-zA-Z0-9]+/', 'link_123456789', $newResult);
        $this->assertSame($result, $newResult);

        $expected = '<form name="link_123456789" style="display:none" method="post" action="/articles/delete/123"><input type="hidden" name="_method" value="POST"></form><a href="#" onclick="if (confirm(&quot;yes/no&quot;)) { document.link_123456789.submit(); } event.returnValue = false; return false;">delete</a>';
        $result = $this->Form->postLink('delete', '/articles/delete/123', ['confirm' => 'yes/no']);

        $result = preg_replace('/link_[a-zA-Z0-9]+/', 'link_123456789', $result);
        $this->assertEquals($expected, $result);
    }

    public function testControl()
    {
        $FormHelper = $this->Form;

        $expected = '<div class="form-group text"><label for="title">Title</label><input type="text" name="title" class="form-control" id="title"></div>';
        $result = $FormHelper->control('title');
        $this->assertEquals($expected, $result);

        $expected = '<div class="group text"><label for="title">Widget Title</label><input type="text" name="title" class="form-control" id="title"></div>';
        $result = $FormHelper->control('title', ['div' => 'group', 'label' => 'Widget Title']);
        $this->assertEquals($expected, $result);

        $expected = '<div class="group text"><label for="title" class="foo">Title</label><input type="text" name="title" class="form-control" id="title"></div>';
        $result = $FormHelper->control('title', [
            'div' => 'group',
            'label' => ['class' => 'foo'],
        ]);
        $this->assertEquals($expected, $result);

        $expected = '<div class="group text"><label for="title" class="foo">Bar</label><input type="text" name="title" class="form-control" id="title"></div>';
        $result = $FormHelper->control('title', [
            'div' => 'group',
            'label' => ['class' => 'foo', 'text' => 'Bar'],
        ]);
        $this->assertEquals($expected, $result);

        $Widget = new Widget();
        ModelRegistry::set('Widget', $Widget);

        $widget = $Widget->new();
        $widget->description = 'Widget Name';
        $widget->invalidate('description', 'invalid description');

        $FormHelper->create($widget);
        $expected = '<div class="form-group textarea error"><label for="description">Description</label><textarea name="description" class="form-control error" id="description">Widget Name</textarea><div class="error-message">invalid description</div></div>';
        $result = $FormHelper->control('description');
        $this->assertEquals($expected, $result);

        $FormHelper->create($widget);
        $expected = '<div class="form-group text required"><label for="name">Name</label><input type="text" name="name" class="form-control" id="name" maxlength="80"></div>';
        $result = $FormHelper->control('name');
        $this->assertEquals($expected, $result);

        $expected = '<div class="form-check checkbox"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" class="form-check-input" id="active"><label for="active" class="form-check-label">Active</label></div>';
        $result = $FormHelper->control('active', ['type' => 'checkbox']);
        $this->assertEquals($expected, $result);

        $result = $this->Form->control('my-select', ['options' => [1 => 'One',2 => 'Two']]);
        $expected = '<div class="form-group select"><label for="my-select">My-select</label><select name="my-select" class="form-control" id="my-select"><option value="1">One</option><option value="2">Two</option></select></div>';
        $this->assertSame($expected, $result);

        $this->Form->view()->set('owners', [1 => 'One',2 => 'Two']);
        $result = $this->Form->control('owner_id');
        $expected = '<div class="form-group select"><label for="owner-id">Owner</label><select name="owner_id" class="form-control" id="owner-id"><option value="1">One</option><option value="2">Two</option></select></div>';
        $this->assertsame($expected, $result);

        $expected = '<input type="hidden" name="id" id="id" maxlength="11">';
        $this->assertSame($expected, $this->Form->control('id', ['type' => 'hidden']));
        
        $Widget = new Widget();
        $widget = $Widget->new();
        $widget->id = 1234;
        $widget->name = 'foo';
        $expected = '<input type="text" name="name" maxlength="80" value="foo">';
        $this->Form->create($widget); // reach create=false for required fields
        $this->assertSame($expected, $this->Form->text('name'));

        $expected = '<div class="form-group password"><label for="password">Password</label><input type="password" name="password" class="form-control" id="password"></div>';
        $this->assertSame($expected, $this->Form->control('password'));

        $expected = '<input type="hidden" name="id" id="id" maxlength="11" value="1234">';
        $this->assertSame($expected, $this->Form->control('id'));

        $expected = '<div class="form-group text"><label for="unkownmodel-id">Id</label><input type="text" name="unkownModel[id]" class="form-control" id="unkownmodel-id"></div>';
        $this->assertSame($expected, $this->Form->control('unkownModel.id'));
    }

    public function testDomId()
    {
        $FormHelper = $this->Form;
        $options = ['id' => true];

        $expected = '<input type="text" name="article" id="article">';
        $this->assertEquals($expected, $FormHelper->text('article', $options));

        $expected = '<input type="text" name="articleId" id="articleid">'; //strtolower
        $this->assertEquals($expected, $FormHelper->text('articleId', $options));

        $expected = '<input type="text" name="article[title]" id="article-title">';
        $this->assertEquals($expected, $FormHelper->text('article.title', $options));
        $expected = '<input type="text" name="article[0][title]" id="article-0-title">';
        $this->assertEquals($expected, $FormHelper->text('article.0.title', $options));
    }

    public function testNumber()
    {
        $result = $this->Form->number('amount');
        $expected = '<input type="text" name="amount">';
        $this->assertSame($expected, $result);

        $result = $this->Form->number('amount', ['value' => 123456789]);
        $expected = '<input type="text" name="amount" value="123,456,789">';
        $this->assertSame($expected, $result);

        $result = $this->Form->number('amount', ['value' => 123456.789]);
        $expected = '<input type="text" name="amount" value="123,456.79">';
        $this->assertSame($expected, $result);
    }

    public function testError()
    {
        $result = $this->Form->error('Something wrong');
        $expected = '<div class="error-message">Something wrong</div>';
        $this->assertSame($expected, $result);
    }

    public function testControlDefaults()
    {
        $defaults = $this->Form->controlDefaults();
        $this->assertIsArray($defaults);

        $this->Form->controlDefaults(['dz' => ['class' => 'new-class']]);
        $defaults = $this->Form->controlDefaults();
        
        $this->assertEquals(['class' => 'new-class'], $this->Form->controlDefaults('dz'));
        $this->assertNull($this->Form->controlDefaults('----'));
    }

    public function testRequestData()
    {
        $this->Form->request()->data('name', 'bobby');
        $expected = '<input type="text" name="name" value="bobby">';
        $this->assertSame($expected, $this->Form->text('name'));
    }

    public function testFormEnd()
    {
        $this->assertEquals('</form>', $this->Form->end());
    }

    public function testFormCsrfField()
    {
        $this->Form->request()->params('csrfToken', '* ORIGINPHP *');
        $result = $this->Form->create();
        $this->assertStringContainsString('<input type="hidden" name="csrfToken" value="* ORIGINPHP *">', $result);
    }

    public function testFormEscapeValues()
    {
        $this->Form->request()->data('email', '"><script>alert(1)</script><"');
        $expected = '<input type="text" name="email" value="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;&lt;&quot;">';
        $this->assertSame($expected, $this->Form->text('email'));
       
        $this->Form->request()->data('name', '>Foo');
        $expected = '<input type="text" name="name" value=">Foo">';
        $this->assertSame($expected, $this->Form->text('name', ['escape' => false]));
    }

    /**
     * I think all this can be re written to each senario, eg using control to create
     */
}
