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

namespace Origin\Test\View\Helper;

use Origin\View\View;
use Origin\View\Helper\FormHelper;
use Origin\Controller\Controller;
use Origin\Controller\Request;
use Origin\Model\ModelRegistry;
use Origin\Model\Model;
use Origin\Utils\Date;
use Origin\Ultis\Time;

class ViewTestsController extends Controller
{
}

class Widget extends Model
{
    public $validate = array(
    'name' => ['rule' => 'notBlank', 'required' => true],
  );
    public $schema = array(
    'id' => ['type' => 'int', 'length' => 11],
    'name' => ['type' => 'varchar', 'length' => 80],
    'description' => ['type' => 'text'],
    'active' => ['type' => 'tinyint', 'length' => 1],
  );

    public function setSchema($schema)
    {
        $this->schema = $schema;
    }
}

class MockFormHelper extends FormHelper
{
    public function setMeta($meta)
    {
        $this->meta = $meta;
    }
}

class FormHelperTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $request = new Request('widgets/edit');
        $controller = new ViewTestsController($request);
        $View = new View($controller);
        $this->FormHelper = new MockFormHelper($View);
        $this->FormHelper->initialize([]);
    }

    public function testCreate()
    {
        $FormHelper = $this->FormHelper;

        $result = $FormHelper->create();
        $expected = '<form method="post" accept-charset="utf-8" action="/widgets/edit">';
        $this->assertEquals($expected, $result);

        $Widget = new Widget();
        $widget = $Widget->newEntity();

        $result = $FormHelper->create($widget);
        $expected = '<form method="post" accept-charset="utf-8" action="/widgets/edit">';
        $this->assertEquals($expected, $result);

        $result = $FormHelper->create($widget, ['type' => 'get']);
        $expected = '<form method="get" accept-charset="utf-8" action="/widgets/edit">';
        $this->assertEquals($expected, $result);

        $result = $FormHelper->create($widget, ['type' => 'file']);
        $expected = '<form enctype="multipart/form-data" method="post" action="/widgets/edit">';
        $this->assertEquals($expected, $result);

        $result = $FormHelper->create($widget, ['class' => 'my-class','id'=>'my-id']);
        $expected = '<form method="post" accept-charset="utf-8" action="/widgets/edit" class="my-class" id="my-id">';
        $this->assertEquals($expected, $result);
    }

    public function testText()
    {
        $FormHelper = $this->FormHelper;

        $expected = '<input type="text" name="article">';
        $this->assertEquals($expected, $FormHelper->text('article'));

        $expected = '<input type="text" name="article" class="form-control">';
        $this->assertEquals($expected, $FormHelper->text('article', array('class' => 'form-control')));

        $expected = '<input type="text" name="article" class="form-control" disabled>';
        $this->assertEquals($expected, $FormHelper->text('article', array('class' => 'form-control', 'disabled' => true)));

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
        $FormHelper = $this->FormHelper;

        $Widget = new Widget();
        $widget = $Widget->newEntity();
        $widget->name = 'foo';

        $widget2 = $Widget->newEntity();
        $widget2->name = 'bar';
        $widget->related = $widget2;

        $widget3 = $Widget->newEntity();
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
        $FormHelper = $this->FormHelper;

        $Widget = new Widget();
        $widget = $Widget->newEntity();
        $widget->name = 'foo';
        $widget->invalidate('name', 'its not bar');

        $widget2 = $Widget->newEntity();
        $widget2->name = 'bar';
        $widget2->invalidate('name', 'its not foo');
        $widget->related = $widget2;

        $widget3 = $Widget->newEntity();
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
        $FormHelper = $this->FormHelper;

        $expected = '<input type="password" name="password">';
        $this->assertEquals($expected, $FormHelper->password('password'));
    }

    public function testHidden()
    {
        $FormHelper = $this->FormHelper;

        $expected = '<input type="hidden" name="id">';
        $this->assertEquals($expected, $FormHelper->hidden('id'));
    }

    public function testTextarea()
    {
        $FormHelper = $this->FormHelper;

        $expected = '<textarea name="description"></textarea>';
        $this->assertEquals($expected, $FormHelper->textarea('description'));

        $expected = '<textarea name="description">some text here</textarea>';
        $this->assertEquals($expected, $FormHelper->textarea('description', ['value' => 'some text here']));

        $Widget = new Widget();
        $widget = $Widget->newEntity();
        $widget->description = 'a description that sells';

        $result = $FormHelper->create($widget);
        $expected = '<textarea name="description">a description that sells</textarea>';
        $this->assertEquals($expected, $FormHelper->textarea('description'));
    }

    public function testSelect()
    {
        $FormHelper = $this->FormHelper;

        $expected = '<select name="status"><option value="0">draft</option><option value="1">new</option><option value="2">published</option></select>';
        $this->assertEquals($expected, $FormHelper->select('status', ['draft', 'new', 'published']));

        $expected = '<select name="status"><option value="">--None--</option><option value="0">draft</option><option value="1">new</option><option value="2">published</option></select>';
        $this->assertEquals($expected, $FormHelper->select('status', ['draft', 'new', 'published'], ['empty' => true]));

        $expected = '<select name="status"><option value="draft">Draft</option><option value="new">New</option><option value="published">Published</option></select>';
        $this->assertEquals($expected, $FormHelper->select('status', ['draft' => 'Draft', 'new' => 'New', 'published' => 'Published']));

        $groupData = array(
      'Group 1' => array(
        'Value 1' => 'Text 1',
        'Value 2' => 'Text 2',
      ),
      'Group 2' => array(
        'Value 3' => 'Text 3',
      ),
    );
        $expected = '<select name="status"><optgroup label="Group 1"><option value="Value 1">Text 1</option><option value="Value 2">Text 2</option></optgroup><optgroup label="Group 2"><option value="Value 3">Text 3</option></optgroup></select>';
        $this->assertEquals($expected, $FormHelper->select('status', $groupData));

        $expected = '<select name="status"><option value="0">draft</option><option value="1">new</option><option value="2" selected>published</option></select>';
        $this->assertEquals($expected, $FormHelper->select('status', ['draft', 'new', 'published'], ['value' => 2]));

        $expected = '<select name="status"><option value="0" selected>draft</option><option value="1">new</option><option value="2">published</option></select>';
        $this->assertEquals($expected, $FormHelper->select('status', ['draft', 'new', 'published'], ['value' => 0]));

        $expected = '<select name="status"><option value="">--None--</option><option value="0">draft</option><option value="1">new</option><option value="2">published</option></select>';
        $this->assertEquals($expected, $FormHelper->select('status', ['draft', 'new', 'published'], ['empty' => true, 'value' => null]));

        $Widget = new Widget();
        $widget = $Widget->newEntity();
        $widget->status = 'new';

        $result = $FormHelper->create($widget);
        $expected = '<select name="status"><option value="draft">Draft</option><option value="new" selected>New</option><option value="published">Published</option></select>';
        $this->assertEquals($expected, $FormHelper->select('status', ['draft' => 'Draft', 'new' => 'New', 'published' => 'Published']));
    }

    public function testDate()
    {
        $FormHelper = $this->FormHelper;
        $placeholder =  'e.g. '. Date::format(date('Y-m-d'));
        $expected = "<input type=\"text\" name=\"date\" placeholder=\"{$placeholder}\">";
        $this->assertEquals($expected, $FormHelper->date('date'));
    }

    public function testTime()
    {
        $FormHelper = $this->FormHelper;
        $placeholder =  'e.g. '. Date::format(date('H:i:s'));
        $expected = "<input type=\"text\" name=\"time\" placeholder=\"{$placeholder}\">";
        $this->assertEquals($expected, $FormHelper->time('time'));
    }

    public function testDatetime()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
          );

        $FormHelper = $this->FormHelper;

        $expected = '<input type="text" name="datetime">';
        $this->assertEquals($expected, $FormHelper->datetime('datetime'));
    }

    public function testFile()
    {
        $FormHelper = $this->FormHelper;

        $expected = '<input type="file" name="submittedfile">';
        $this->assertEquals($expected, $FormHelper->file('submittedfile'));
    }

    public function testLabel()
    {
        $FormHelper = $this->FormHelper;

        $expected = '<label for="name">name</label>';
        $this->assertEquals($expected, $FormHelper->label('name'));

        $expected = '<label for="name">Custom</label>';
        $this->assertEquals($expected, $FormHelper->label('name', 'Custom'));

        $Widget = new Widget();
        $widget = $Widget->newEntity();
        $widget->name = 'Widget Name';

        $result = $FormHelper->create($widget);
        $expected = '<label for="name">name</label>';
        $this->assertEquals($expected, $FormHelper->label('name'));
    }

    public function testButton()
    {
        $FormHelper = $this->FormHelper;

        $expected = '<button type="submit">save</button>';
        $this->assertEquals($expected, $FormHelper->button('save'));

        $expected = '<button type="button">save</button>';
        $this->assertEquals($expected, $FormHelper->button('save', array('type' => 'button')));
    }

    public function testCheckbox()
    {
        $FormHelper = $this->FormHelper;

        $expected = '<input type="hidden" name="agree" value="0"><input type="checkbox" name="agree" value="1">';
        $this->assertEquals($expected, $FormHelper->checkbox('agree'));

        $expected = '<input type="checkbox" name="agree" value="1">';
        $this->assertEquals($expected, $FormHelper->checkbox('agree', ['hiddenField' => false]));

        # Test values
        $Widget = new Widget();
        $widget = $Widget->newEntity();
        $widget->active = true;
        $widget->in_stock = false;
        $result = $FormHelper->create($widget);

        $expected = '<input type="checkbox" name="active" value="1" checked>';
        $this->assertEquals($expected, $FormHelper->checkbox('active', ['hiddenField'=>false]));
        
        $expected = '<input type="checkbox" name="in_stock" value="1">';
        $this->assertEquals($expected, $FormHelper->checkbox('in_stock', ['hiddenField'=>false]));
    }

    public function testRadio()
    {
        $FormHelper = $this->FormHelper;

        $expected = '<label for="duplicates-0"><input type="radio" name="duplicates" value="0" id="duplicates-0">Create New</label><label for="duplicates-1"><input type="radio" name="duplicates" value="1" id="duplicates-1">Overwrite</label><label for="duplicates-2"><input type="radio" name="duplicates" value="2" id="duplicates-2">Delete</label>';
        $this->assertEquals($expected, $FormHelper->radio('duplicates', ['Create New', 'Overwrite', 'Delete']));
    }

    public function testPostLink()
    {
        $FormHelper = $this->FormHelper;
        $expected = '<form name="link_123456789" style="display:none" method="post" action="/articles/delete/123"><input type="hidden" name="_method" value="POST"></form><a href="#" onclick="document.link_123456789.submit();">delete</a>';
        $result = $this->FormHelper->postLink('delete', '/articles/delete/123');

        $result = preg_replace('/link_[a-zA-Z0-9]+/', 'link_123456789', $result);
        $this->assertEquals($expected, $result);

        $expected = '<form name="link_123456789" style="display:none" method="post" action="/articles/delete/123"><input type="hidden" name="_method" value="POST"></form><a href="#" onclick="if (confirm(&quot;yes/no&quot;)) { document.link_123456789.submit(); } event.returnValue = false; return false;">delete</a>';
        $result = $this->FormHelper->postLink('delete', '/articles/delete/123', ['confirm' => 'yes/no']);

        $result = preg_replace('/link_[a-zA-Z0-9]+/', 'link_123456789', $result);
        $this->assertEquals($expected, $result);
    }

    public function testControl()
    {
        $FormHelper = $this->FormHelper;

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

        $widget = $Widget->newEntity();
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
    }

    public function testDomId()
    {
        $FormHelper = $this->FormHelper;
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
}
