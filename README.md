Shopware 5 plugin base
=============

This library contains abstractions that may be useful in all custom plugins.
It provides following features:
* Database migrations on plugin installation and update
  * Shopware configuration migrations (soon)
  * Custom attribute migrations
  * Custom model migrations (soon)

Installation
------------

Add a composer.json to your plugin

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "ssh://git@gitlab.momocode.de:9022/base/sw5-plugin-base.git"
        }
    ],
    "require": {
        "momocode/sw5-plugin-base": "@dev"
    }
}

```

Now run `composer install` in the plugin directory.

Add composer autoloader to plugin bootstrap class and let your plugin inherit the abstraction

```php
<?php

namespace MyPlugin;

use Momocode\ShopwareBase\Plugin;

// Autload extra dependencies
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

class MyPlugin extends Plugin {}
```

Migrations
----------
To perform migrations on plugin installation and update, you can profit from the plugin 
base file which autoloads the migration files on installation or update. 
To use them you have to place your migration files in the `Migration` folder of your plugin.

Attribute Migration
-------------------
Here is an example for an attribute migration file inherited from the `AbstractAttributeMigration` in the `Migration/Attribute` folder in your plugin.
It is important that the file and class has the suffix "Migration" in the name.
The plugin base class will find this migration file and on plugin installation it will
create all attributes which are required for the installed version and on plugin update
it will create or update only the attributes for the new version.
```php
<?php

namespace MomoMailjet\Migration\Attribute;

use Momocode\ShopwareBase\Migration\Attribute\AbstractAttributeMigration;
use Shopware\Bundle\AttributeBundle\Service\TypeMapping;

class UserMigration extends AbstractAttributeMigration
{
    /**
     * Get table name
     *
     * @return string
     */
    public function getTableName()
    {
        return 's_user_attributes';
    }

    /**
     * Get column prefix
     *
     * @return string
     */
    protected function getColumnPrefix()
    {
        return 'momo_mailjet';
    }

    /**
     * Get default options for fields
     *
     * @return array
     */
    protected function getDefaultOptions()
    {
        return [
            'displayInBackend' => true,
            'custom' => false,
            'position' => $this->getPosition(),
        ];
    }

    /**
     * Get attribute fields for this migration
     *
     * @return array
     */
    protected function getFields()
    {
        $fields = [];

        $fields[] = [
            'email',
            TypeMapping::TYPE_STRING,
            [
                'label' => 'Mailjet E-Mail',
            ],
            '1.0.0',
        ];

        return $fields;
    }
}

```

Emotion Widgets
---------------

This library provides an abstraction for emotion widgets. It encapsulates installation
and update routines. In the plugin base file Widgets are automatically installed and updated,
if they are in the folder `Widgets` and if the file and class name ends with `Widget`.
Here is an example of a widget class in your plugins `Widgets` folder:

```php
<?php

namespace MomoMailjet\Widgets;

use Momocode\ShopwareBase\Widget\AbstractWidget;

class MailjetBuiltInWidget extends AbstractWidget
{
    /**
     * Name of the plugin the widget belongs to
     *
     * @return string
     */
    public function getPluginName()
    {
        return 'MomoMailjet';
    }

    /**
     * Name of the widget
     *
     * @return string
     */
    public function getWidgetName()
    {
        return 'Mailjet: Eingebautes Widget';
    }

    /**
     * XType to listen for
     *
     * @return string
     */
    public function getXType()
    {
        return 'emotion-components-mailjet-built-in-widget';
    }

    /**
     * Register javascript for the widget
     *
     * @return string
     */
    protected function getBackendJsPath()
    {
        return 'backend/emotion/mailjet_built_in_widget/bootstrap.js';
    }

    /**
     * Widget options
     *
     * @return array
     */
    public function getWidgetOptions()
    {
        return [
            'template' => 'mailjet_built_in_widget', // must be created in widgets/emotion/components/
            'cls' => 'emotion-mailjet-built-in-widget', // additional css class
            'description' => 'Built-In Anmeldeformular', // Backend description
        ];

    }

    /**
     * List of fields
     *
     * @return array
     */
    public function getWidgetFields()
    {
        $fields = [];

        $fields[] = [
            'xtype' => 'textfield',
            'name' => 'text',
            'fieldLabel' => 'Simple text field',
            'position' => 10,
            'allowBlank' => true,
        ];

        return $fields;
    }

    /**
     * Update widget on plugin update
     *
     * @param string $oldPluginVersion
     *
     * @throws \Exception
     */
    public function updateWidget($oldPluginVersion)
    {
        // TODO: Implement updateWidget() method.
    }

    /**
     * Enrich assign data array with data from the widget
     *
     * @param array $data
     *
     * @return array
     */
    protected function getTemplateData($data)
    {
        // TODO: Implement getTemplateData() method.
    }
}

```
The defined fields in the `getWidgetFields()` function are installed ...
The `updateWidget()` function is called on plugin updates and there you can perform this
operations depending on the new plugin version:

##### Add new field
Add your field to the `getWidgetFields()` function and add this to the `updateWidget()`
function if you want to add the field for example in plugin version 1.0.1:
```php
if (version_compare('1.0.1', $oldPluginVersion, '>')) {
    $this->addField('new_field_name');
}
```

##### Update field
Update your field in the `getWidgetFields()` function and add this to the `updateWidget()`
function if you want to update the field for example in plugin version 1.0.1:
```php
if (version_compare('1.0.1', $oldPluginVersion, '>')) {
    $this->updateField('updated_field_name');
}
```

##### Remove field
Remove your field from the `getWidgetFields()` function and add this to the `updateWidget()`
function if you want to remove the field for example in plugin version 1.0.1:
```php
if (version_compare('1.0.1', $oldPluginVersion, '>')) {
    $this->removeField('field_name');
}
```

##### Template Data



##### Service Definition
We need to create the service definition and tag it as a subscriber so 
the widget can register the backend template and transform the frontend template data on request.  

```xml
<service id="momo_mailjet.widgets.mailjet_built_in_widget"
         class="MomoMailjet\Widgets\MailjetBuiltInWidget"
         parent="momocode.shopware_base.widget.abstract_widget">
    <tag name="shopware.event_subscriber"/>
</service>  
```
Now you need this three files in the path you defined in your widget class in the 
`getBackendJsPath()` function:

##### bootstrap.js:
```js
//{block name="backend/emotion/view/detail/elements/base"}
//{$smarty.block.parent}
//{include file='backend/emotion/mailjet_built_in_widget/Emotion.view.components.MailjetBuiltInWidget.js'}
//{include file='backend/emotion/mailjet_built_in_widget/Emotion.view.detail.elements.MailjetBuiltInWidget.js'}
//{/block}
```
Adds the other both files to the backend template.

##### Emotion.view.components.MailjetBuiltInWidget.js:
```js
//{block name="emotion_components/backend/mailjet_built_in_widget"}
Ext.define('Shopware.apps.Emotion.view.components.MailjetBuiltInWidget', {
    extend: 'Shopware.apps.Emotion.view.components.Base',
    alias: 'widget.emotion-components-mailjet-built-in-widget'
});
//{/block}
```
In the alias the part after `widget.` must be the same as you defined in your widget class
in the `getXType()` function.

##### Emotion.view.detail.elements.MailjetBuiltInWidget.js:
```js
//{namespace name=backend/plugins/momomailjet/emotion}
Ext.define('Shopware.apps.Emotion.view.detail.elements.MailjetBuiltInWidget', {
    extend: 'Shopware.apps.Emotion.view.detail.elements.Base',
    alias: 'widget.detail-element-emotion-components-mailjet-built-in-widget',
    icon: 'data:image/png;base64,iVBORw0KGg...',
    compCls: 'emotion--mailjet-built-in-widget'
});
```
In the alias the part after `widget.detail-element-` must be the same as you defined in your widget class
in the `getXType()` function.

On top of that you need a frontend template file for your widget in your theme or also 
in your plugins views directory. It must be placed in the `widgets/emotion/components`
folder and named as you defined in the `getWidgetOptions()` functions `template` attribute.
Here is an example:

##### mailjet_built_in_widget.tpl

```smarty

```

At least you need a subscriber to register the view path of your plugin:

```php
<?php

namespace MomoMailjet\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Template_Manager;

class TemplateSubscriber implements SubscriberInterface
{
    /**
     * @var Enlight_Template_Manager
     */
    protected $templateManager;

    /**
     * @var string
     */
    protected $pluginBaseDirectory;

    /**
     * @param Enlight_Template_Manager $templateManager
     * @param string $pluginBaseDirectory
     */
    public function __construct(Enlight_Template_Manager $templateManager, $pluginBaseDirectory)
    {
        $this->templateManager = $templateManager;
        $this->pluginBaseDirectory = $pluginBaseDirectory;
    }

    /**
     * Use the early "Enlight_Controller_Action_PreDispatch" event to register the template directory of the plugin.
     *
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch' => 'onPreDispatch',
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Emotion' => 'onPostDispatchBackendEmotion',
        ];
    }

    /**
     * On pre dispatch
     *
     * @param \Enlight_Controller_ActionEventArgs $args
     */
    public function onPreDispatch(\Enlight_Controller_ActionEventArgs $args)
    {
        $this->templateManager->addTemplateDir($this->pluginBaseDirectory . '/Resources/views');
    }

    /**
     * Register templates for custom designer components
     *
     * @param \Enlight_Controller_ActionEventArgs $args
     */
    public function onPostDispatchBackendEmotion(\Enlight_Controller_ActionEventArgs $args)
    {
        $controller = $args->getSubject();
        $view = $controller->View();

        if ($view) {
            $view->addTemplateDir($this->pluginBaseDirectory . '/Resources/views/');
        }
    }
}
```

```xml
<service id="momo_mailjet.subscriber.template_subscriber" class="MomoMailjet\Subscriber\TemplateSubscriber">
    <argument type="service" id="template" />
    <argument>%momo_mailjet.plugin_dir%</argument>
    <tag name="shopware.event_subscriber"/>
</service>
```