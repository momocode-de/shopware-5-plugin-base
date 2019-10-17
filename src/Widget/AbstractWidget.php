<?php

namespace Momocode\ShopwareBase\Widget;

use Doctrine\ORM\NoResultException;
use Enlight\Event\SubscriberInterface;
use Shopware\Components\Logger;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Emotion\Library\Component;
use Shopware\Models\Emotion\Library\Field;
use Shopware\Models\Plugin\Plugin;

/**
 * This is an abstraction for all widgets
 *
 * @author Moritz Müller <moritz@momocode.de>
 */
abstract class AbstractWidget implements SubscriberInterface
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ModelManager
     */
    protected $modelManager;

    /**
     * AbstractWidget constructor.
     *
     * @param Logger $logger
     * @param ModelManager $modelManager
     */
    public function __construct(Logger $logger, ModelManager $modelManager)
    {
        $this->logger = $logger;
        $this->modelManager = $modelManager;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Widgets_Emotion_AddElement' => 'onEmotionAddElement',
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Emotion' => 'onPostDispatchBackendEmotion',
        ];
    }

    /**
     * Process teaser container information an load categories for rendering
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function onEmotionAddElement(\Enlight_Event_EventArgs $args)
    {
        $data = $args->getReturn();
        $element = $args->get('element');
        $xType = $element['component']['xType'];
        if ($xType && $xType === $this->getXType()) {
            try {
                $args->setReturn($this->getTemplateData($data));
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
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

        $jsPath = $this->getBackendJsPath();
        if ($jsPath) {
            $view->extendsTemplate($jsPath);
        }
    }

    /**
     * Name of the plugin the widget belongs to
     *
     * @return string
     */
    abstract public function getPluginName();

    /**
     * Name of the widget
     *
     * @return string
     */
    abstract public function getWidgetName();

    /**
     * XType to listen for
     *
     * @return string
     */
    abstract public function getXType();

    /**
     * Register javascript for the widget
     *
     * @return string
     */
    abstract protected function getBackendJsPath();

    /**
     * Widget options
     *
     * @return array
     */
    abstract public function getWidgetOptions();

    /**
     * List of fields
     *
     * @return array
     */
    abstract public function getWidgetFields();

    /**
     * Update widget on plugin update
     *
     * @param string $oldPluginVersion
     *
     * @throws \Exception
     */
    abstract public function updateWidget($oldPluginVersion);

    /**
     * Enrich assign data array with data from the widget
     *
     * @param array $data
     *
     * @return array
     */
    protected function getTemplateData($data)
    {
        return $data;
    }

    /**
     * Get plugin entity
     *
     * @return Plugin
     *
     * @throws NoResultException
     */
    public function getPlugin()
    {
        $plugin = $this->modelManager->getRepository(Plugin::class)->findOneBy(['name' => $this->getPluginName()]);

        if (!$plugin) {
            throw new NoResultException();
        }

        return $plugin;
    }

    /**
     * Helper to add a field later to the target structure
     *
     * @param string $name
     *
     * @throws NoResultException
     * @throws \Exception
     */
    protected function addField($name)
    {
        try {
            $component = $this->getComponent();
        } catch (NoResultException $e) {
            // Component does not exist -> Probably we are in the installation routine transaction
            return;
        }

        $fieldDefinition = $this->getFieldDefinition($name);

        $component->createField($fieldDefinition);
    }

    /**
     * Helper to update an existing field to the target structure
     *
     * @param string $name Name of the field to update
     *
     * @throws \Exception
     */
    protected function updateField($name)
    {
        try {
            $component = $this->getComponent();
        } catch (NoResultException $e) {
            // Component does not exist -> Probably we are in the installation routine transaction
            return;
        }

        $mapping = [
            'xtype' => 'xType',
            'store' => 'store',
            'displayField' => 'displayField',
            'valueField' => 'valueField',
            'fieldLabel' => 'fieldLabel',
            'allowBlank' => 'allowBlank',
            'supportText' => 'supportText',
            'helpText' => 'helpText',
            'position' => 'position',
        ];

        $fieldDefinition = $this->getFieldDefinition($name);

        $builder = $this->modelManager->createQueryBuilder()->update(Field::class, 'f');
        // Update all fields that are available
        foreach ($mapping as $key => $fieldName) {
            if (isset($fieldDefinition[$key])) {
                $builder->set('f.' . $fieldName, $builder->expr()->literal($fieldDefinition[$key]));
            }
        }

        $builder->where($builder->expr()->eq('f.name', $builder->expr()->literal($name)));
        $builder->andWhere($builder->expr()->eq('f.component', $component->getId()));
        $builder->getQuery()->execute();
    }

    /**
     * Delete field for a component
     *
     * @param string $name
     *
     * @throws \Exception
     */
    protected function removeField($name)
    {
        try {
            $component = $this->getComponent();
        } catch (NoResultException $e) {
            // Component does not exist -> Probably we are in the installation routine transaction
            return;
        }

        $builder = $this->modelManager->createQueryBuilder()->delete(Field::class, 'f');
        $builder->where($builder->expr()->eq('f.name', $builder->expr()->literal($name)));
        $builder->andWhere($builder->expr()->eq('f.component', $component->getId()));
        $builder->getQuery()->execute();
    }

    /**
     * Get a component
     *
     * @return Component
     *
     * @throws NoResultException
     * @throws \Exception
     */
    public function getComponent()
    {
        $component = $this->modelManager->getRepository(Component::class)->findOneBy(
            [
                'name' => $this->getWidgetName(),
                'pluginId' => $this->getPlugin()->getId(),
            ]
        );

        if (!$component) {
            throw new NoResultException();
        }

        return $component;
    }

    /**
     * Get definition of a field
     *
     * @param string $name
     *
     * @return array
     *
     * @throws NoResultException
     */
    private function getFieldDefinition($name)
    {
        foreach ($this->getWidgetFields() as $fieldDefinition) {
            if ($fieldDefinition['name'] == $name) {
                return $fieldDefinition;
            }
        }
        throw new NoResultException();
    }
}
