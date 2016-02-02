<?php

namespace Aventura\Wprss\Core;

/**
 * @since [*next-version*]
 */
class Factory extends Plugin\FactoryAbstract
{
    public function _create($data = array())
    {
        $plugin = new Plugin($data);
        $factory = new ComponentFactory($plugin);
        $plugin->setFactory($factory);

        $plugin->setLogger($factory->createLogger());
        $plugin->setEventManager($factory->createEventManager());

        $plugin->hook();

        return $plugin;
    }
}